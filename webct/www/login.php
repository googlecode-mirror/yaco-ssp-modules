<?php
/* WebCT SSO */

/* Calculates a MAC (message authentication code) from an array of strings and a secret.*/
    function calculateMac($config, $params)
    {
        // get ascii of all param values
        $data = implode('', $params);
        $asciivalue = 0;
        $size = strlen($data);
        for($i=0; $i<$size; $i++)
            $asciivalue+=ord(substr($data, $i, 1));
        // get md5 of ascii value and secret
        $mac = md5($asciivalue . $config->getValue('secret',''));
        return $mac;
    }

    function get_consortia_id($config, $username){
        $protocol = $config->getValue('protocol', 'http');
        $host = $config->getValue('host', 'localhost');
        $port = $config->getValue('port', '80');
        if($protocol == 'https')
            $host = 'ssl://' . $host;
        $GET_CID = fsockopen($host, $port, $errCode, $errStr, 30);
        if(!$GET_CID)
            exit("<p>fsocket error $errCode: $errStr</p>");
        $now = time();
        $mac = calculateMac($config,
            array('get', 'consortia_id', $username, $now));
        $postUrl = "/webct/systemIntegrationApi.dowebct?adapter=standard" .
                "&operation=get" .
                "&option=consortia_id" .
                "&webctid=$username" .
                "&timestamp=$now" .
                "&auth=$mac";
        $requestStr = "POST $postUrl HTTP/1.1\r\n" .
                        "Host: $host\r\n" .
                        "Content-type: application/x-www-form-urlencoded\r\n" .
                        "Connection: Close\r\n" .
                        "\r\n";
        fputs($GET_CID, $requestStr);
        // get the response headers
        $headerStr = '';
        while($str = trim(fgets($GET_CID, 4096)))
            $headerStr .= $str . "\n";
        // get the response body
        $body = '';
        while(!feof($GET_CID))
            $body .= fread($GET_CID, 4096);
        fclose($GET_CID);
        // strip the XML tags
        $count = preg_match('/<consortiaid>(.*)<\/consortiaid>/', $body, $found);
        if($count==false || $count==0)
            exit("No consortia ID found.");
        $consortia_id=$found[1];
        return $consortia_id;
    }

    function get_sso_url($config, $username){
        $protocol = $config->getValue('protocol', 'http');
        $host = $config->getValue('host', 'localhost');
        $port = $config->getValue('port', '80');
        $initial_url = $config->getValue($initial_url,
            '/webct/viewMyWebCT.dowebct');
        $consortia_id = get_consortia_id($config, $username);
        if ($consortia_id == 'null')
            throw new Exception("User $username does not exist in WebCT");
        $now = time();
        $params = array($consortia_id, $now, $initial_url);
        $mac =  calculateMac($config, $params);
        $url = $protocol . '://' .
            $host . ':' . $port .
            '/webct/public/autosignon?' . 'wuui=' . $consortia_id .
            '&timestamp=' . $now . '&url=' . urlencode($initial_url) .
            '&mac=' . $mac;
        return $url;
    }

$config = SimpleSAML_Configuration::getInstance();
$session = SimpleSAML_Session::getInstance();
$webctconfig = SimpleSAML_Configuration::getConfig('webct.php');
$authsource = $webctconfig->getValue('auth', 'saml');
$useridattr = $webctconfig->getValue('useridattr', 'eduPersonPrincipalName');

if ($session->isValid($authsource)) {
    $attributes = $session->getAttributes();
    // Check if userid exists
    if (!isset($attributes[$useridattr]))
        throw new Exception('User ID is missing');
    $userid = $attributes[$useridattr][0];
} else {
    SimpleSAML_Auth_Default::initLogin($authsource, SimpleSAML_Utilities::selfURL());
};

$url = get_sso_url($webctconfig, $userid);
header("Location: $url");
