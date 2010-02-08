<?php

/* WebCT SSO and on-the-fly provisioning for simpleSAMLphp
Dependencies: php curl support
DSN for Oracle:
// Connect to a database defined in tnsnames.ora ($ORACLE_HOME/network/admin)
oci:dbname=mydb

// Connect using the Oracle Instant Client
oci:dbname=//localhost:1521/mydb
*/


define("WEBCT_SI_URL", "systemIntegrationApi.dowebct");
define("WEBCT_SSO_URL", "public/autosignon");

define("WEBCT_CONFIG_FILENAME", 'module_webct.php');


class sspmod_webct_Connector
{

    var $webct_base_url = 'http://localhost/webct/';
    var $secret;
    var $url = '';

    /* Read parameters from config */
    function __construct(){
        SimpleSAML_Logger::debug("WebCT: Init connector, getting config");
        /// Get parameters from config file
        $config = SimpleSAML_Configuration::getConfig(WEBCT_CONFIG_FILENAME);
        // Basic params for WebCT SSO
        $this->authsource = $config->getValue('auth', 'default-sp');
        $this->webct_base_url = $config->getValue('webct_base_url');
        $this->webct_internal_url = $config->getValue('webct_internal_url',
            $this->webct_base_url);
        $this->secret = $config->getValue('secret', '');
        $this->url = $config->getValue('initial_url', 'viewMyWebCT.dowebct');
        $this->logout_redirect_url = $config->getValue('logout_redirect_url',
            '/');
        $this->userid_attr = $config->getValue('userid_attr',
            'eduPersonPrincipalName');
        $this->redirect403_url = $config->getValue('redirect403_url',
            './webct_403.php');
        // User provisioning params
        $this->sn_attr = $config->getValue('sn_attr', 'sn');
        $this->givenName_attr = $config->getValue('givenName_attr',
            'givenName');
        $this->mail_attr = $config->getValue('mail_attr', 'mail');
        // course data
        $this->courses_enrollments_attr = $config->getValue(
            'courses_enrollments_attr', 'schacUserStatus');
        $this->course_pattern = $config->getValue(
            'course_pattern',
            '(?P<code>.*):(?P<period>.*):(?P<role>.*):(?P<status>.*)');

        // course code translation params
        $this->default_source = $config->getValue('default_source', 'WebCT');
        $course_map_mode =$config->getValue('course_map_mode', NULL);
        if ($course_map_mode == 'sql'){
            $dsn =$config->getValue('dsn');
            $dbuser =$config->getValue('dbuser');
            $dbpassword =$config->getValue('dbpassword');
            $sql =$config->getValue('sql');
            if (empty($dsn) || empty($dbuser) || empty($sql))
                throw new SimpleSAML_Error_Exception("Missing or empty "
                   . "'dsn', 'dbuser' or 'sql' "
                   . "for course_map_mode 'sql' in webct.php configuration.");
            $this->dsn = $dsn;
            $this->dbuser = $dbuser;
            $this->dbpassword = $dbpassword;
            $this->sql = $sql;
        } elseif ($course_map_mode == 'map'){
            $map = $config->getValue('course_map');
            if (empty($map))
                throw new SimpleSAML_Error_Exception("Missing or empty 'course_map' "
                    ."for course_map_mode 'map' in webct.php configuration.");
            $this->course_map = $map;
        } elseif ($course_map_mode == 'expr') {
            $this->expr = $config->getValue('expr');
        }
        $this->course_map_mode = $course_map_mode;
        // role and status translation maps
        $this->role_map = $config->getValue('role_map');
        $this->status_map = $config->getValue('status_map');
    }

    function check_user_and_courses(&$webct_uid, &$webct_courses){
        if (empty($webct_courses) && empty($webct_uid))
            return FALSE;
        return TRUE;
    }

    /* main method, which does it all, calling any needed functions */
    function main(){
        // Get attributes
        $attrs = $this->get_saml_attributes();

        // Get user id
        $userid = $attrs[$this->userid_attr][0];
        $this->userid = $userid;

        // check if user already logged in
        $session = SimpleSAML_Session::getInstance();
        $webct_uid = $session->getData('WebCT', 'uid');

        if (empty($webct_uid)){
            // User has not already logged in
            // Check if any courses where supplied
            $webct_courses = $this->get_webct_courses($attrs);
            // See if the user was already provisioned and her WebCT uid
            $webct_uid = $this->get_webct_user_id($userid);
            // Check if user / courses are correct
            if (!$this->check_user_and_courses($webct_uid, $webct_courses))
                $this->redirect403();

            // If user doesn't already exist in WebCT, create.
            if (empty($webct_uid)){
                $webct_uid = $this->create_user($userid, $attrs);
            }
            // Store uid if user re-enters in the same session
            $session->setData('WebCT', 'uid', $webct_uid);

            // Add / update courses
            $webct_courses = $this->update_webct_courses(
                $webct_courses, $userid);

            // enroll user in course sections
            $this->enroll_user($userid, $webct_courses);

            SimpleSAML_Logger::info("WebCT: Login '$userid'.");
        } else {
            SimpleSAML_Logger::debug("WebCT: Reusing session for '$userid'.");
        }

        $url = $this->get_sso_url($webct_uid);
        $this->goto_webct($url);
    }

    /* Goto WebCT, given the complete url
       By defaul, a redirect to WebCT, but could also show
       an intermediate page. */
    function goto_webct($url){
        header("Location: $url");
        die;
    }


    /* Get SAML attributes. If not logged in, redirect, etc. */
    function get_saml_attributes(){
        $as = new SimpleSAML_Auth_Simple($this->authsource);
        $as->requireAuth();
        $attrs = $as->getAttributes();

        // Check if userid exists
        if (empty($attrs[$this->userid_attr]))
            throw new SimpleSAML_Error_Exception('User ID is missing');

        return $attrs;
    }


    /* Extract courses from attributes and translate to WebCT IMS */
    function get_webct_courses($attrs){
        // get user's course enrollments
        $courses_key = $this->courses_enrollments_attr;

        // translate codes & filter out anything we won't provide
        if (!empty($attrs[$courses_key])){
            $courses = $attrs[$courses_key];
            $webct_courses = $this->translate_course_array($courses);
        } else {
            $webct_courses = NULL;
        }
        return $webct_courses;
    }

    /* Load course map from sql */
    function load_sql_course_map(){
        SimpleSAML_Logger::debug("WebCT: loading course map");
        $session = SimpleSAML_Session::getInstance();
        $map = $session->getData('course_map','course_map');
        $map = array();

        if (empty($map)){
            $map = array();
            $dbh = new PDO($this->dsn, $this->dbuser, $this->dbpassword);
            foreach ($dbh->query($this->sql) as $row){
                $map[$row['CODE'].':'.$row['PERIOD']] = array(
                    'source' => (empty($row['IMS_SOURCE']) ?
                        $this->default_source : $row['IMS_SOURCE']),
                    'id' => $row['IMS_ID']);
            }
            $session->setData('course_map', 'course_map', $map);
        }
        return $map;
    }


    /* Return Unicode ord of an UTF-8 character
       Thanks to: http://www.php.net/manual/en/function.ord.php#77905
    */
    function uniord($c) {
        $h = ord($c{0});
        if ($h <= 0x7F) {
            return $h;
        } else if ($h < 0xC2) {
            return false;
        } else if ($h <= 0xDF) {
            return ($h & 0x1F) << 6 | (ord($c{1}) & 0x3F);
        } else if ($h <= 0xEF) {
            return ($h & 0x0F) << 12 | (ord($c{1}) & 0x3F) << 6
                                     | (ord($c{2}) & 0x3F);
        } else if ($h <= 0xF4) {
            return ($h & 0x0F) << 18 | (ord($c{1}) & 0x3F) << 12
                                     | (ord($c{2}) & 0x3F) << 6
                                     | (ord($c{3}) & 0x3F);
        } else {
            return false;
        }
    }



    /* Calculate checksum of a string */
    function chksum($string){
        $chksum = 0;
        $size = mb_strlen($string, 'UTF-8');
        for($i=0; $i<$size; $i++)
            $chksum += $this->uniord(mb_substr($string, $i, 1, 'UTF-8'));
        return $chksum;
    }


    /* Calculates a MAC (message authentication code) from an array
        of strings and a secret.*/
    function calculate_mac($params)
    {
        // get ascii of all param values
        $data = implode('', $params);
        $chksum = $this->chksum($data);
        $mac = md5($chksum . $this->secret);
        return $mac;
    }


    /* urlencode parameters for GET request */
    function urlencode_params($params){
        $res = '';
        foreach ($params as $key => $value)
            $res .= "&$key=" . urlencode($value);
        return substr($res,1);
    }


    /* Makes a call to WebCT Sistem Integration API (siapi)
    There are two apdaters: standar & ims
    Both may or not require xml parameter, depending on the operation.
    If ims with xml paramter, 'enterprise' record with timestamp and
    properties are added automatically. Don't include it in the xml.
    See: http://www.imsglobal.org/enterprise/
    Some messages, especially 'personlist' are NOT enterprise.
    */
    function siapi_call($adapter, $params, $xml=""){
        SimpleSAML_Logger::debug("WebCT: preparing siapi call to adater " .
            "'$adapter' with params:\n" . var_export($params, TRUE));
        $params['timestamp'] = time();
        $params_mac = $params;
        if (!empty($xml)){
            // Add 'ims enterprise' common string
            if ($adapter == 'ims'){
                $gen_date = date(DATE_ISO8601);
                $xml = "<?xml version=\"1.0\" encoding=\"UTF-8\"?>
<enterprise xmlns=\"http://www.imsproject.org/xsd/imsep_rootv1p01\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:webct=\"http://www.webct.com/IMS\">
    <properties>
        <datasource>Confia</datasource>
        <datetime>$gen_date</datetime>
    </properties>\n" . $xml . "\n</enterprise>";
            }
            SimpleSAML_Logger::debug("and xml: \n" . var_export($xml, TRUE));
            $params_mac['chksum'] = "" . $this->chksum($xml);
        }
        $mac = $this->calculate_mac($params_mac);
        $params_send = array('adapter' => $adapter);
        foreach ($params as $key => $value)
            $params_send[$key] = $value;
        if (!empty($xml)){
            // Write XML to file, 'cause we need it for curl
            $filepath = tempnam('/tmp','WCT');
            file_put_contents($filepath, $xml);
            $params_send['FILENAME'] = "@$filepath";
        }
        $params_send['auth'] = $mac;
        $ch = curl_init();
        $url = $this->webct_internal_url . WEBCT_SI_URL;
        if (!empty($xml)){
            curl_setopt($ch, CURLOPT_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params_send);
            SimpleSAML_Logger::debug("WebCT: POST to WebCT '$url' " .
                "with body:\n" . var_export($params_send, TRUE));
        } else {
            $url .= '?' . $this->urlencode_params($params_send);
            SimpleSAML_Logger::debug("WebCT: GET to WebCT '$url'.");
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec($ch);
        SimpleSAML_Logger::debug("WebCT: Reponse: " .
            var_export($response, TRUE));
        curl_close($ch);

        if (!empty($filepath))
            unlink($filepath);
        if ($response === FALSE)
            throw new SimpleSAML_Error_Exception("Error en la comunicación "
                . "con WebCT.");
        if (strstr($response, "Invalid Message Authentication Code") !== FALSE)
            throw new SimpleSAML_Error_Exception("La clave secreta de "
                . "comunicacion con WebCT es incorrecta o los relojes "
                . "no están sincronizados.");
        return $response;
    }


    /* Create or update user in WebCT */
    function create_user($username, $attributes){
        $sn = $attributes[$this->sn_attr][0];
        $gn = $attributes[$this->givenName_attr][0];
        $mail = $attributes[$this->mail_attr][0];
        $gen_date = date(DATE_ISO8601);
        $xml = "
            <person>
                <sourcedid>
                    <source>confia</source>
                    <id>$username</id>
                </sourcedid>
                <userid>$username</userid>
                <name>
                    <fn>$gn</fn>
                    <n>
                        <family>$sn</family>
                        <given>$gn</given>
                    </n>
                </name>
                <email>$mail</email>
            </person>";
        $params = array(
            'action' => 'import',
            'option' => 'restrict',
        );
        $body = $this->siapi_call('ims', $params, $xml);
        if ($body === FALSE || strstr($body, "success") == FALSE){
            SimpleSAML_Logger::error("WebCT: Couldn't create user " .
                "'$username' with attributes:\n" .
                var_export($attributes, TRUE) . "\nThe result was: " .
                var_export($body, TRUE));
                throw new SimpleSAML_Error_Exception("No se puede crear "
                    . "usuario en esta plataforma: $username !");
        }
        $webct_uid = $this->get_webct_user_id($username);
        return $webct_uid;
    }


    function get_enrollments($lc_source, $lc_id, $user_source, $user_id){
        // TODO: still not working
        $params = array(
            'action' => 'export',
            'option' => 'group_record',
            'imssource' => $lc_source,
            'imsid' => $lc_id,
        );
        $xml = "<list>
   <person>
     <sourcedid>
       <source>$user_source</source>
       <id>$user_id</id>
     </sourcedid>
   </person>
</list>";
        $body = $this->siapi_call('ims', $params, $xml);
        return $body;
    }


    /* Get WebCT user id for a username. Returns FALSE if doesn't exist.
       Normally, it's the same, but may be different if it contains
       strange characters or there are more than one user with the same
       username but different institutions.
    */
    function get_webct_user_id($username){
        SimpleSAML_Logger::debug("WebCT: Getting WebCT user id for " .
            "'$username'.");
        $params = array(
            'operation' => 'get',
            'option' => 'consortia_id',
            'webctid' => $username,
        );
        $body = $this->siapi_call('standard', $params);
        if ($body !== FALSE){
            // strip the XML tags
            $count = preg_match('/<consortiaid>(.*)<\/consortiaid>/',
                $body, $found);
            if(!empty($found)){
                $consortia_id = $found[1];
                if ($consortia_id != "null"){
                    SimpleSAML_Logger::debug("WebCT: WebCT user id for " .
                        "'$username' is '$consortia_id'.");
                    $this->webct_uid = $consortia_id;
                    return $consortia_id;
                }
                SimpleSAML_Logger::debug("WebCT: get_webct_user_id: '$username' " .
                    "doesn't exist in WebCT.");
                return FALSE;
            }
        }
        SimpleSAML_Logger::error("WebCT: Unexpected result for " .
            "get_webct_user_id: " . var_export($body, TRUE));
        return FALSE;
    }


    /* Get a users IMS source/id */
    function get_person_ims_source($username){
        SimpleSAML_Logger::debug("WebCT: getting ims source for " .
            "person '$username'");
        $params = array(
            'action' => 'configure',
            'option' => 'get_person_ims_info',
            'webctid' => $username,
        );
        $body = $this->siapi_call('ims', $params);
        // strip the XML tags
        $pattern = "|<sourcedid><source>(.*)</source><id>(.*)</id>" .
            "</sourcedid>|";
        $count = preg_match($pattern, $body, $found);
        if($count==false || $count==0){
            SimpleSAML_Logger::error("WebCT: Unexpected result for " .
                "get_person_ims_source: " . var_export($body, TRUE));
            throw new SimpleSAML_Error_Exception("No ims source found. "
                . "Response was: " . var_export($body, TRUE));
        }
        if ($found[1] == 'null'){
            SimpleSAML_Logger::debug("WebCT: ims source for " .
                "'$username' is null.");
            return NULL;
        }
        $res = array('source' => $found[1], 'id' => $found[2]);
        SimpleSAML_Logger::debug("WebCT: ims source for " .
            "'$username' is: " . var_export($res, TRUE));
        return $res;
    }


    /* enroll, unenroll or lock user */
    function enroll_user($username, $webct_courses){
        if (empty($webct_courses))
            return TRUE;
        SimpleSAML_Logger::debug("WebCT: enrolling user '$username' in " .
            "courses " . var_export($webct_courses, TRUE));
        $res = $this->get_person_ims_source($username);
        if ($res == NULL)
            throw new SimpleSAML_Error_Exception("User '$username' unknown "
                . "in WebCT.");
        $user_ims_source = $res['source'];
        $user_ims_id = $res['id'];
        $params = array(
            'action' => 'import',
            'option' => 'restrict',
            'webctid' => $username,
        );
        // status = 1=active, 0=inactive
        // recstatus = 1=add, 2=edit, 3=delete
        $recstatus = 1;
        // recstatus ignored (1 add, 2 update,
        //    default add if doesn't exist, else update)
        // subrole student: AUD=auditor
        // subrole instructor: Subordinate=Non-primary section instructor
        // subrole instructor: TA=Teaching assistant
        $gen_date = date(DATE_ISO8601);
        $xml = "";
        $course_msg = array();
        foreach ($webct_courses as $course){
            $ims_source = $course['ims_source'];
            $course_section_ims_source = $ims_source['source'];
            $course_section_ims_id = $ims_source['id'];
            $course_msg[] = $course['code'].':'.$course['period'] .
                " ($course_section_ims_id, $course_section_ims_source)";
            $subrole = '';
            $role = $course['role'];
            if (is_array($role)){
                $subrole = $role[1];
                $role = $role[0];
            }
            $status = $course['status'];
            $xml .= "
                <membership>
                    <sourcedid>
                        <source>$course_section_ims_source</source>
                        <id>$course_section_ims_id</id>
                    </sourcedid>
                    <member>
                        <sourcedid>
                            <source>$user_ims_source</source>
                            <id>$user_ims_id</id>
                        </sourcedid>
                        <idtype>1</idtype>
                        <role roletype=\"$role\">
                            <subrole>$subrole</subrole>
                            <status>$status</status>
                            <userid>$username</userid>
                        </role>
                    </member>
                </membership>";
        }
        $course_msg  = implode("; ", $course_msg);
        $msg = " enrolling user '$username' in courses $course_msg. ";
        $body = $this->siapi_call('ims', $params, $xml);
        if (stripos($body, "success") === FALSE){
            SimpleSAML_Logger::error("WebCT: Error $msg Result is: " .
                var_export($body, TRUE));
            throw new SimpleSAML_Error_Exception("WebCT Error al inscribir "
                . "en asigaturas " . $course_msg . ": "
                . var_export($body, TRUE));
        }
        if (stripos($body, "failed") !== FALSE){
            // partially failed
            SimpleSAML_Logger::warning("WebCT: Problems $msg");
            $this->warn(array("{webct:webct:warning_invalid_course_codes}",
                    array('%COURSE_CODES%' => $course_msg)), $username);
        } else {
            SimpleSAML_Logger::debug("WebCT: Success $msg");
            return TRUE;
        }
    }


    /* Show a warning, let user continue */
    function warn($msg, $username){
        $config = SimpleSAML_Configuration::getInstance();
        $t = new SimpleSAML_XHTML_Template($config,
            'webct:webct_warning.php');
        $t->data['warning'] = $msg;
        $url = $this->get_sso_url($this->webct_uid);
        $t->data['url'] = $url;
        $t->data['username'] = $username;
        $t->data['%EMAIL%'] = $config->getString('technicalcontact_email',
            NULL);
        $t->show();
        die;
    }

    /* Redirect to a 403 Forbidden page */
    function redirect403(){
        SimpleSAML_Utilities::redirect($this->redirect403_url);
        die;
    }

    /* Update webct course map (e.g. customize/filter) */
    function update_webct_courses($webct_courses, $userid){
        return $webct_courses;
    }

    /* Get SSO URL for WebCT User.
       Doesn't check if user exists in WebCT. (Use get_webct_user_id.)
    */
    function get_sso_url($webct_uid){
        SimpleSAML_Logger::debug("WebCT: Getting SSO URL for WebCT user " .
            "'$webct_uid'.");
        $params = array(
            'wuui' => $webct_uid,
            'timestamp' => time(),
            'url' => $this->url);
        $mac =  $this->calculate_mac($params);
        $params['mac'] = $mac;
        $url = $this->webct_base_url . WEBCT_SSO_URL . '?' .
            $this->urlencode_params($params);
        SimpleSAML_Logger::debug("WebCT: SSO URL for '$webct_uid' is: $url");
        return $url;
    }


    /* Translate attribute values into local LMS courses */
    function translate_course_array($courses){
        SimpleSAML_Logger::debug("WebCT: Translate courses: " .
            var_export($courses, TRUE));
        // translate 'urn:tenena.org:schacStatus....' into something
        // more userful for webct.
        if ($this->course_map_mode == 'sql')
            $this->course_map = $this->load_sql_course_map();
        $webct_courses = array();
        if (empty($courses))
            return $webct_courses;
        foreach ($courses as $course){
            $pattern = '|' . $this->course_pattern . '|';
            $count = preg_match($pattern, $course, $found) ;
            /* if not found, just ignore values */
            if ($count>0){
                $code = $found['code'];
                $period = $found['period'];
                $role = $this->translate_role($found['role']);
                $status = $this->translate_status($found['status']);
                $res = $this->translate_course_code($code, $period);
                if (!empty($res)){
                    $webct_courses[] = array(
                        'code' => $code,
                        'period' => $period,
                        'ims_source' => $res,
                        'role' => $role,
                        'status' => $status);
                }
            }
        }
        SimpleSAML_Logger::debug("WebCT: Translate courses result: " .
            var_export($webct_courses, TRUE));
        return $webct_courses;
    }


    /* Translate user status. */
    function translate_status($status){
        SimpleSAML_Logger::debug("WebCT: Translate status: " .
            var_export($status, TRUE));
        $status = strtolower($status);
        if (!array_key_exists($status, $this->status_map))
            throw new SimpleSAML_Error_Exception("Invalid status "
                . "'$status' for webct connector. Not in status_map.");
        $res = $this->status_map[$status];
        SimpleSAML_Logger::debug("WebCT: Translate status result: " .
            var_export($res, TRUE));
        return $res;
    }

    /* Translate user status. */
    function translate_role($role){
        SimpleSAML_Logger::debug("WebCT: Translate role: " .
            var_export($role, TRUE));
        if (!array_key_exists($role, $this->role_map))
            throw new SimpleSAML_Error_Exception("Invalid role '$role' for webct ".
                " connector. Not in role_map.");
        $res = $this->role_map[$role];
        SimpleSAML_Logger::debug("WebCT: Translate role result: " .
            var_export($res, TRUE));
        return $res;
    }


    /* Translate course, period to ims source & id.
       Returs: array('source' => ims_source, 'id' => ims_id)
    */
    function translate_course_code($code, $period){
        SimpleSAML_Logger::debug("WebCT: Translate course code: " .
            var_export($code, TRUE) . "   for period: " .
            var_export($period, TRUE));
        $source = $this->default_source;
        if (!empty($this->course_map_mode)){
            if ($this->course_map_mode == 'expr'){
                $code_block = "return " . $this->expr . ';';
                $res = eval($code_block);
                $source = $res['source'];
                $id = $res['id'];
            } else {
                $key = "$code:$period";
                if (array_key_exists($key, $this->course_map)){
                    $res = $this->course_map[$key];
                    $source = $res['source'];
                    $id = $res['id'];
                } else {
                    SimpleSAML_Logger::warning("WebCT: Can't find course " .
                        "translation for $key!");
                    $id = '-----';
                    $source = '';
                }
            }
        } else {
            $id = $code;
        }
        $res = array('source' => $source, 'id' => $id);
        SimpleSAML_Logger::debug("WebCT: Translate course code result: " .
            var_export($res, TRUE));
        return $res;
    }


    /* Single Logout */
    function logout(){
        $as = new SimpleSAML_Auth_Simple($this->authsource);
        $as->logout($this->logout_redirect_url);
    }
}
