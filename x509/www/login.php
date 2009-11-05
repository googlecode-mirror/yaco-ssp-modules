<?php

/* Load the configuration. */
$config = SimpleSAML_Configuration::getInstance();


/* Load the session of the current user. */
$session = SimpleSAML_Session::getInstance();
if($session == NULL) {
	SimpleSAML_Utilities::fatalError($session->getTrackID(), 'NOSESSION');
}


if (!array_key_exists('AuthState', $_REQUEST)) {
	throw new SimpleSAML_Error_BadRequest('Missing AuthState parameter.');
} else {
	$authStateId = $_REQUEST['AuthState'];
}

if(array_key_exists('SSL_CLIENT_CERT', $_SERVER) && ($_SERVER['SSL_CLIENT_CERT']!=NULL)  ) {
	$error = sspmod_x509auth_Auth_Source_X509Auth::handleLogin($authStateId, $_SERVER['SSL_CLIENT_CERT']);
}else {
	$error = "no_cert_provided";
}


//Login Page
$t = new SimpleSAML_XHTML_Template($config, 'x509auth:temp-login.php', 'x509auth:dict-x509auth'); //(configuracion, template, diccionario)
$t->data['header'] = 'simpleSAMLphp: CertValidator login';
$t->data['stateparams'] = array('AuthState' => $authStateId);
$t->data['error'] = $error;
$t->show();
exit();
?>
