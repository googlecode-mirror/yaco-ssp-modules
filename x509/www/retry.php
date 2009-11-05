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


header("Location: " . SimpleSAML_Module::getModuleURL('x509auth/login.php') . '?AuthState=' . $authStateId);
?>
