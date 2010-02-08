<?php
/*
 * The configuration of simpleSAMLphp WebCT SSO
 */

$config = array (
	'auth' => 'saml',
    'webct_base_url' => 'http://localhost:8280/webct/',
    // WebCT SSO & SLO parameters
    'secret' => 'WebCTSSO',
    'initial_url' => 'viewMyWebCT.dowebct',
    'userid_attr' => 'eduPersonPrincipalName',
    'logout_redirect_url' => '/',
    // user provisioning parameters
    'sn_attr' => 'sn',
    'givenName_attr' => 'givenName',
    'mail_attr' => 'mail',

    // course provisioning parameters
    'courses_enrollments_attr' => 'schacUserStatus',
    'course_pattern' => 'urn:mace:terena.org:schac:userStatus:'.
        'es:campusandaluzvirtual.es:'.
        '(?P<code>.*):(?P<period>.*):(?P<role>.*):(?P<status>.*)',
    'course_map_mode' => 'sql',  // NULL, 'sql', 'map' or 'expr'
    'default_source' => 'WebCT',
    // 'sql' course code translation
    'dsn' => 'oci:dbname=xe',
    'dbuser' => 'user',
    'dbpassword' => 'password',
    'sql' => "select YEAR as PERIOD, COURSE_CODE as CODE, WEBCT_ID as IMS_ID FROM COURSE_MAPPING",
    // 'map' array; only used if course_map_mode == 'map'
    'course_map' => array(
        '12345678:2009-10' => 'TCS2',
        '87654321:2009-10' => 'TCS1',
    ),
    // 'expr' course translations: takes '$code' and '$period' as parameters
    'expr' => 'array("source" => "WebCT",
                     "id" => "CAV" . $code . "_" . substr($period,2,2) .
                        substr($period,5,2) . ".default",
                     )',

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
