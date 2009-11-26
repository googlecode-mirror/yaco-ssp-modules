<?php

$session = SimpleSAML_Session::getInstance();
$config = SimpleSAML_Configuration::getInstance();
$myconfig = SimpleSAML_Configuration::getConfig('config-attributevalidator.php');

$authsource = $myconfig->getValue('auth', 'login-admin');


$SPMetadata = array();

if(isset($_REQUEST['token'])) {
	$SPMetadata['token'] = $_REQUEST['token'];
}
if(isset($_REQUEST['mail'])) {
	$SPMetadata['mail'] = $_REQUEST['mail'];
}

if ($session->isValid($authsource)) {
    $attributes = $session->getAttributes();
} else {
    SimpleSAML_Auth_Default::initLogin(
        $authsource,
        SimpleSAML_Utilities::selfURL(),
        NULL,
        array(
            'SPMetadata' => $SPMetadata
        )
    );
}

unset($_POST); //Show the languages bar if reloaded

//Login Page
$t = new SimpleSAML_XHTML_Template($config, 'attributevalidator:validate.php', 'attributevalidator:attributevalidator');
$t->data['header'] = $t->t('page_header');;
$t->data['remaining'] = $session->remainingTime();
$t->data['sessionsize'] = $session->getSize();
$t->data['attributes'] = sspmod_attributevalidator_AttributeValidator::validateAttributes($attributes);
$t->data['logouturl'] = SimpleSAML_Module::getModuleURL('core/authenticate.php') . '?logout';
$t->data['icon'] = 'bino.png';
$t->show();
