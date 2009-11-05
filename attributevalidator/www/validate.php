<?php

$session = SimpleSAML_Session::getInstance();
$config = SimpleSAML_Configuration::getInstance();
$myconfig = SimpleSAML_Configuration::getConfig('config-attributevalidator.php');

$authsource = $myconfig->getValue('auth', 'login-admin');

if ($session->isValid($authsource)) {
	$attributes = $session->getAttributes();
	// Check if userid exists
} else {
	SimpleSAML_Auth_Default::initLogin(
		$authsource,
		SimpleSAML_Utilities::selfURL(),
		NULL,
		array(
			'SPMetadata' => array(
				'token' => $_REQUEST['token'],
				'mail' => $_REQUEST['mail']
			)
		)
	);
}

unset($_POST); //Show the languages bar if reloaded

//Login Page
$t = new SimpleSAML_XHTML_Template($config, 'attributevalidator:validate.php', 'attributevalidator:attributevalidator'); //(configuracion, template, diccionario)
$t->data['header'] = 'Validación de atributos SimpleSAML';
$t->data['remaining'] = $session->remainingTime();
$t->data['sessionsize'] = $session->getSize();
$t->data['attributes'] = sspmod_attributevalidator_AttributeValidator::validateAttributes($attributes);
$t->data['logouturl'] = SimpleSAML_Utilities::selfURLNoQuery() . '?logout';
$t->data['icon'] = 'bino.png';
$t->show();

?>