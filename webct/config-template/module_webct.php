<?php
/*
 * The configuration of simpleSAMLphp WebCT SSO
 */

$config = array (
	'auth' => 'saml',
    'webct_base_url' => 'http://localhost:8280/webct/',
    // WebCT SSO parameters
    'secret' => 'WebCTSSO',
    'initial_url' => 'viewMyWebCT.dowebct',
    'userid_attr' => 'eduPersonPrincipalName',
    // user provisioning parameters
    'sn_attr' => 'sn',
    'givenName_attr' => 'givenName',
    'mail_attr' => 'mail',

    // course provisioning parameters
    'courses_enrollments_attr' => 'schacUserStatus',
    'course_pattern' => 'urn:mace:terena.org:schac:userStatus:'.
        'es:campusandaluzvirtual.es:'.
        '(?P<code>.*):(?P<period>.*):(?P<role>.*):(?P<status>.*)',
    'course_map_mode' => 'sql',  // NULLL, 'sql' or 'map'
    'default_source' => 'WebCT',
    // 'sql' course code translation

    'dsn' => 'oci:dbname=xe',
    'dbuser' => 'user',
    'dbpassword' => 'password',
    'sql' => "select YEAR as PERIOD, COURSE_CODE as CODE, WEBCT_ID as IMS_ID FROM COURSE_MAPPING",
    // 'map' array; only used if course_map_mode == 'map'
    'course_map' => array(
        '12345678:2009' => 'TCS2',

    ),

    // ims name to webct/ims role + subrole codes. At present, we only support section enrollment (not course
    'role_map' => array(
        'learner' => '01',
        'student' => '01',
        'auditor' => array('01', 'AUD'),
        'instructor' => '02',
        'teaching assistant' => array('02', 'TA'),
        'teachingassistant' => array('02', 'TA'),
        'content developer' => '03',
        'section designer' => '03',
    ),
    'status_map' => array(
        'active' => 1,
        'inactive' => 0,
    ),
);
