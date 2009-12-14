<?php
/*
 * The configuration of simpleSAMLphp WebCT SSO
 */

$config = array (
	'auth' => 'saml',
	'useridattr' => 'eduPersonPrincipalName',
    'courses_enrollments_attr' => 'schacUserStatus',
    'webct_base_url' => 'http://localhost:8280/webct/';
    'secret' => 'WebCTSSO',
    'initial_url' => 'viewMyWebCT.dowebct',
    'policy' => array('create_user','enroll_user_section'),
    'course_pattern' => 'urn:mace:terena.org:schac:userStatus:es:campusandaluzvirtual.es:(.*):(.*):(.*):(.*)',

);

