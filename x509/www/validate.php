<?php

/* Load the configuration. */
$config = SimpleSAML_Configuration::getInstance();

$x509Cert ='';

if(array_key_exists('x509Cert', $_POST) && ($_POST['x509Cert']!=NULL)  ) {
	$x509Cert = $_POST['x509Cert'];

	$result = sspmod_x509_CertValidator::validateCert($x509Cert);
	if ($result == 'cert_validation_success') {
		$error = NULL;
		$success = $result;
	} else {
		$error = $result;
		$success = NULL;
	}

} else {
	$error = NULL;
	$success = NULL;
}

unset($_POST); //Show the languages bar if reloaded

//Login Page
$t = new SimpleSAML_XHTML_Template($config, 'x509:validate.php', 'x509:certvalidator');
$t->data['header'] = 'simpleSAMLphp: CertValidator login';
$t->data['stateparams'] = array('AuthState' => $authStateId);
$t->data['x509Cert'] = $x509Cert;
$t->data['error'] = $error;
$t->data['success'] = $success;
$t->show();
?>