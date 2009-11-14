<?php
/* WebCT SSO */

define("WEBCT_SI_URL", "/webct/systemIntegrationApi.dowebct");

class WebCT_Login_Provisioning
{

    var $protocol = 'http';
    var $host = 'localhost';
    var $port = '80';
    var $secret;
    var $url = '';

    function __construct(){
        $config = SimpleSAML_Configuration::getConfig('webct.php');
        $this->authsource = $config->getValue('auth', 'saml');
        $this->useridattr = $config->getValue('useridattr', 'eduPersonPrincipalName');
        $this->protocol = $config->getValue('protocol', 'http');
        $this->host = $config->getValue('host', 'localhost');
        $this->port = $config->getValue('port',
            ($this->protocol == 'https' ? 443 : 80));
        $this->secret = $config->getValue('secret', '');
        $this->url = $config->getValue('initial_url',
            '/webct/viewMyWebCT.dowebct');

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


    function siapi_call($adapter, $params, $xml=""){
        $params['timestamp'] = time();
        $params_mac = $params;
        if (!empty($xml))
            $params_mac['chksum'] = "" . $this->chksum($xml);
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
        $url = "$this->protocol://$this->host:$this->port" . WEBCT_SI_URL;
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
        $gen_date = date(DATE_ISO8601);
        $sn = array_key_exists('sn', $attributes) ? $attributes['sn'][0] : $attributes['surname'][0];
        $gn = array_key_exists('gn', $attributes) ? $attributes['gn'][0] : $attributes['givenName'][0];
        $mail = $attributes['mail'][0];
        $xml = "<?xml version=\"1.0\"?>
<enterprise xmlns=\"http://www.imsproject.org/xsd/imsep_rootv1p01\" xmlns:xsi=\"http://www.w3.org/2001/XMLSchema-instance\" xmlns:webct=\"http://www.webct.com/IMS\">
    <properties>
        <datasource>Confia</datasource>
        <datetime>$gen_date</datetime>
    </properties>
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
    </person>
</enterprise>";
        $params = array(
            'action' => 'import',
            'option' => 'restrict',
        );
        $body = $this->siapi_call('ims', $params, $xml);
        if (strstr($body, "success") == FALSE)
            return FALSE;
        return TRUE;
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
        $url = $this->protocol . '://' .
            $this->host . ':' .
            $this->port .
            '/webct/public/autosignon?' .
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
    if (!isset($attributes[$webct->useridattr]))
        throw new Exception('User ID is missing');
    $userid = $attributes[$webct->useridattr][0];
} else {
    SimpleSAML_Auth_Default::initLogin($webct->authsource,
        SimpleSAML_Utilities::selfURL());
};

$url = $webct->get_sso_url($userid);
if ($url == FALSE){
    $res = $webct->create_user($userid, $attributes);
    if ($res == TRUE)
        $url = $webct->get_sso_url($userid);
    else
        throw new Exception("Can't create user: $userid in WebCT!");
}
header("Location: $url");
