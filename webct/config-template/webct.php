<?php
/*
 * The configuration of simpleSAMLphp WebCT SSO
 */

$config = array (
	'auth' => 'saml',
	'useridattr' => 'eduPersonPrincipalName',
    'host' => 'localhost',
    'port' => '8280',
    'protocol' => 'http',
    'secret' => 'WebCTSSO',
    'initial_url' => '/webct/viewMyWebCT.dowebct',
    'policy' => array('create_user','enroll_user_section'),
);

