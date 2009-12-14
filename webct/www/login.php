<?php
/* WebCT SSO and on-the-fly provisioning
Dependencies: php curl support
*/

define("WEBCT_SI_URL", "systemIntegrationApi.dowebct");
define("WEBCT_SSO_URL", "public/autosignon");

class WebCT_Login_Provisioning
{

    var $webct_base_url = 'http://localhost/webct/';
    var $secret;
    var $url = '';

    function __construct(){
        $config = SimpleSAML_Configuration::getConfig('webct.php');
        $this->authsource = $config->getValue('auth', 'saml');
        $this->webct_base_url = $config->getValue('webct_base_url');
        $this->secret = $config->getValue('secret', '');
        $this->url = $config->getValue('initial_url',
            'viewMyWebCT.dowebct');
        $this->userid_attr = $config->getValue(
            'userid_attr', 'eduPersonPrincipalName');
        $this->courses_enrollments_attr = $config->getValue(
            'courses_enrollments_attr', 'schacUserStatus');
        $this->course_pattern = $config->getValue(
            'course_pattern', '(.*):(.*):(.*):(.*)');

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
            curl_setopt($ch, CURL_POST, 1);
            curl_setopt($ch, CURLOPT_POSTFIELDS, $params_send);
        } else {
            $url .= '?' . $this->urlencode_params($params_send);
        }
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);

        $response = curl_exec($ch);
        curl_close($ch);
        if (!empty($filepath))
            unlink($filepath);
        return $response;
    }

    function create_user($username, $attributes){
        $sn = array_key_exists('sn', $attributes) ? $attributes['sn'][0] : $attributes['surname'][0];
        $gn = array_key_exists('gn', $attributes) ? $attributes['gn'][0] : $attributes['givenName'][0];
        $mail = $attributes['mail'][0];
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
        if (strstr($body, "success") == FALSE)
            return FALSE;
        return TRUE;
    }

    function get_enrollments($lc_source, $lc_id, $user_source, $user_id){
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
        $params = array(
            'operation' => 'get',
            'option' => 'consortia_id',
            'webctid' => $username,
        );
        $body = $this->siapi_call('standard', $params);
        // strip the XML tags
        $count = preg_match('/<consortiaid>(.*)<\/consortiaid>/',
            $body, $found);
        if($count==false || $count==0)
            exit("No tag consortiaid found. Response was: $body");
        $consortia_id = $found[1];
        return $consortia_id;
    }

    function get_person_ims_source($username){
        $params = array(
            'action' => 'configure',
            'option' => 'get_person_ims_info',
            'webctid' => $username,
        );
        $body = $this->siapi_call('ims', $params);
        // strip the XML tags
        $pattern = '|<sourcedid><source>(.*)</source><id>(.*)</id></sourcedid>|';
        $count = preg_match($pattern, $body, $found);
        if($count==false || $count==0)
            exit("No ims source found. Response was: $body");
        if ($found[1] == 'null')
            return NULL;
        $res = array('source' => $found[1], 'id' => $found[2]);
        return $res;
    }

    function enroll_ims($username, $course_section_ims_source,
            $course_section_ims_id){
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
        // status = 1=active, 2=inactive
        $status=1;
        // recstatus = 1=add, 2=edit, 3=delete
        $recstatus = 1;
        // recstatus ignored (1 if doesn't exist, 2 if exists)
        //   problem: is access denied from WebCT GUI, grants access again.
        // roletype = 01=student, 02=instructor, 03=designer/content developer
        // subrole student: AUD=auditor
        // subrole instructor: Subordinate=Non-primary section instructor
        // subrole instructor: TA=Teaching assistant
        $role = "01";
        $subrole = "";

        $gen_date = date(DATE_ISO8601);
        $xml = "
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

        $body = $this->siapi_call('ims', $params, $xml);
        if (strstr($body, "success") == FALSE)
            throw new Exception("WebCT Error: $body");
        return TRUE;
    }

    function get_sso_url($username){
        $consortia_id = $this->get_consortia_id($username);
        if ($consortia_id == 'null'){
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
        return $url;
    }
}

$config = SimpleSAML_Configuration::getInstance();
$session = SimpleSAML_Session::getInstance();
$webct = new WebCT_Login_Provisioning();

if ($session->isValid($webct->authsource)) {
    $attributes = $session->getAttributes();
    // Check if userid exists
    if (!isset($attributes[$webct->userid_attr]))
        throw new Exception('User ID is missing');
    $userid = $attributes[$webct->userid_attr][0];
} else {
    SimpleSAML_Auth_Default::initLogin($webct->authsource,
        SimpleSAML_Utilities::selfURL());
};

// get automatic sign-on URL from WebCT for the user.
$url = $webct->get_sso_url($userid);
if ($url == FALSE){
    // if user doesn't exist, create it
    $res = $webct->create_user($userid, $attributes);
    if ($res == TRUE)
        $url = $webct->get_sso_url($userid);
    else
        throw new Exception("Can't create user: $userid in WebCT!");
}
// check if user enrollments
$courses = $attributes[$webct->courses_enrollments_attr];


foreach ($courses as $course){
    // translate course to ims source & id
    $pattern = '|' . $webct->course_pattern . '|';
    $count = preg_match($pattern, $course, $found) ;
    if (!empty($found)){
        $course_code = $found[1];
        $course_period = $found[2];
        $course_role = $found[3];
        $course_status = $found[4];
        $ims_source = 'WebCT';
        $ims_id = $course_code; // WebCT course section

        // enroll user in course (section)
        $webct->enroll_ims($userid, $ims_source, $ims_id);
    }
}

header("Location: $url");
