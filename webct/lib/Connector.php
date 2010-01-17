<?php
/* WebCT SSO and on-the-fly provisioning
Dependencies: php curl support
DSN for Oracle:
// Connect to a database defined in tnsnames.ora ($ORACLE_HOME/network/admin)
oci:dbname=mydb

// Connect using the Oracle Instant Client
oci:dbname=//localhost:1521/mydb
*/

/*  TODO:
 - Asserts of config at __construct time
 - Error message if course code non-existent in LMS (ims import fails)
   refactor
 - Allow configure 403 redirect page.
 - If course provisioning fails, e.g., some data is incorrect, SSO fails.
   Should be more solid, display an error and let user proceed.
 - Still no logout / single logout
 - Use singleton for this class?
 - The course code mapping is loaded for each user, is it possible to
   share it? (e.g. memcached, etc.)
*/


define("WEBCT_SI_URL", "systemIntegrationApi.dowebct");
define("WEBCT_SSO_URL", "public/autosignon");

define("WEBCT_CONFIG_FILENAME", 'module_webct.php');

class sspmod_webct_Connector
{

    var $webct_base_url = 'http://localhost/webct/';
    var $secret;
    var $url = '';

    function __construct(){
        SimpleSAML_Logger::debug("WebCT: Init connector, getting config");
        $config = SimpleSAML_Configuration::getConfig(WEBCT_CONFIG_FILENAME);
        // Basic params for WebCT SSO
        $this->authsource = $config->getValue('auth', 'default-sp');
        $this->webct_base_url = $config->getValue('webct_base_url');
        $this->secret = $config->getValue('secret', '');
        $this->url = $config->getValue('initial_url',
            'viewMyWebCT.dowebct');
        $this->userid_attr = $config->getValue(
            'userid_attr', 'eduPersonPrincipalName');
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

        // course code translation
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
                    ."for course_map_mode 'sql' in webct.php configuration.");
            $this->dsn = $dsn;
            $this->dbuser = $dbuser;
            $this->dbpassword = $dbpassword;
            $this->sql = $sql;
        } elseif ($course_map_mode == 'map'){
            $map = $config->getValue('course_map');
            if (empty($map))
                throw new Exception("Missing or empty 'course_map' "
                    ."for course_map_mode 'map' in webct.php configuration.");
            $this->course_map = $map;
        }
        $this->course_map_mode = $course_map_mode;
        // role and status translation maps
        $this->role_map = $config->getValue('role_map');
        $this->status_map = $config->getValue('status_map');
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



    /* Calculate checksum of a string */
    function chksum($string){
        $chksum = 0;
        $size = strlen($string);
        for($i=0; $i<$size; $i++)
            $chksum += ord(substr($string, $i, 1));
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
        SimpleSAML_Logger::debug("WebCT: preparing siapi call to adater '$adapter' " .
            "with params:\n" . var_export($params, TRUE));
        $params['timestamp'] = time();
        $params_mac = $params;
        if (!empty($xml)){
            // Add 'ims enterprise' common string
            if ($adapter == 'ims'){
                $gen_date = date(DATE_ISO8601);
                $xml = "<?xml version=\"1.0\"?>
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
        $url = $this->webct_base_url . WEBCT_SI_URL;
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
        SimpleSAML_Logger::debug("WebCT: Reponse: " . var_export($response, TRUE));
        curl_close($ch);

        if (!empty($filepath))
            unlink($filepath);
        if ($response === FALSE)
            throw new Exception("Error en la comunicación con WebCT.");
        if (strstr($response, "Invalid Message Authentication Code") !== FALSE)
            throw new Exception("La clave secreta de comunicacion con WebCT "
                . "es incorrecta.");
        return $response;
    }

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
            SimpleSAML_Logger::error("WebCT: Unexpected result when ".
              "creating user '$username' with attributes:\n" .
              var_export($attributes, TRUE) . "\nThe result was: " .
              var_export($body, TRUE));
            return FALSE;
        }
        return TRUE;
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

    function get_consortia_id($username){
        SimpleSAML_Logger::debug("WebCT: Getting consortia_id for '$username'");
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
                    SimpleSAML_Logger::debug("WebCT: consortia_id for " .
                        "'$username' is '$consortia_id'.");
                    return $consortia_id;
                }
            }
        }
        SimpleSAML_Logger::error("WebCT: Unexpected result for " .
            "get_consortia_id: " . var_export($body, TRUE));
        return FALSE;

    }

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
        $pattern = '|<sourcedid><source>(.*)</source><id>(.*)</id></sourcedid>|';
        $count = preg_match($pattern, $body, $found);
        if($count==false || $count==0){
            SimpleSAML_Logger::error("WebCT: Unexpected result for " .
                "get_person_ims_source: " . var_export($body, TRUE));
            throw new Exception("No ims source found. Response was: " .
                var_export($body, TRUE));
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

    function enroll_user($username, $webct_courses){
        SimpleSAML_Logger::debug("WebCT: enrolling user '$username' in " .
            "courses " . var_export($webct_courses, TRUE));
        $res = $this->get_person_ims_source($username);
        if ($res == NULL)
            throw new Exception("User $username unknown in WebCT.");
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
        foreach ($webct_courses as $course){
            $ims_source = $course['ims_source'];
            $course_section_ims_source = $ims_source['source'];
            $course_section_ims_id = $ims_source['id'];
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

        $body = $this->siapi_call('ims', $params, $xml);
        if (strstr($body, "success") == FALSE){
            SimpleSAML_Logger::error("WebCT: Error when enrolling user " .
                "'$username'. Result is: " . var_export($body, TRUE));
            throw new Exception("WebCT Error: " . var_export($body, TRUE));
        }
        SimpleSAML_Logger::debug("WebCT: Success enrolling user '$username'.");
        return TRUE;
    }

    function get_sso_url($username){
        SimpleSAML_Logger::debug("WebCT: Getting SSO URL for '$username'.");
        $consortia_id = $this->get_consortia_id($username);
        if ($consortia_id == FALSE){
            SimpleSAML_Logger::debug("WebCT: Result get SSO: '$username' ".
                "-> does not exist.");
            return FALSE;
        }
        $params = array(
            'wuui' => $consortia_id,
            'timestamp' => time(),
            'url' => $this->url);
        $mac =  $this->calculate_mac($params);
        $params['mac'] = $mac;
        $url = $this->webct_base_url . WEBCT_SSO_URL . '?' .
            $this->urlencode_params($params);
        SimpleSAML_Logger::debug("WebCT: SSO URL for '$username' is: $url");
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
            throw new Exception("Invalid status '$status' for webct " .
                " connector. Not in status_map.");
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
            throw new Exception("Invalid role '$role' for webct ".
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
            var_export($code, TRUE) . "   for period: " . var_export($period, TRUE));
        $source = $this->default_source;
        if (!empty($this->course_map_mode)){
            $key = "$code:$period";
            if (array_key_exists($key, $this->course_map)){
                $res = $this->course_map[$key];
                $source = $res['source'];
                $id = $res['id'];
            }
        } else {
            $id = $code;
        }
        $res = array('source' => $source, 'id' => $id);
        SimpleSAML_Logger::debug("WebCT: Translate course code result: " .
            var_export($res, TRUE));
        return $res;
    }
}
