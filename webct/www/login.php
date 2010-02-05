<?php

// requires SSP >= 1.5
$webct = new sspmod_webct_Connector;
$as = new SimpleSAML_Auth_Simple($webct->authsource);
$as->requireAuth();
$attrs = $as->getAttributes();


// Check if userid exists
if (empty($attrs[$webct->userid_attr]))
    throw new Exception('User ID is missing');
$userid = $attrs[$webct->userid_attr][0];



// check if user already logged in
$session = SimpleSAML_Session::getInstance();
$webct_uid = $session->getData('WebCT', 'uid');

if (empty($webct_uid)){
    // User has not already logged in

    // get user's course enrollments
    $courses_key = $webct->courses_enrollments_attr;

    // translate codes & filter out anything we won't provide
    if (!empty($attrs[$courses_key])){
        $courses = $attrs[$courses_key];
        $webct_courses = $webct->translate_course_array($courses);
    } else {
        $webct_courses = NULL;
    }

    // check if user exists (get sso url)
    $webct_uid = $webct->get_user_id($userid);

    // if no enrollments and not already user -> 403
    if (empty($webct_courses) && empty($webct_uid)){
        SimpleSAML_Utilities::redirect('./webct_403.php');
        die;
    }

    // if user does not exist in WebCT, create account
    if (empty($webct_uid)){
        $res = $webct->create_user($userid, $attrs);
        if ($res == FALSE)
            throw new Exception("No se puede crear usuario "
                . "en esta plataforma: $userid !");
        $webct_uid = $webct->get_user_id($userid);
    }
    // Store uid for the next time
    $session->setData('WebCT', 'uid', $webct_uid);

    // enroll user in course sections
    if (!empty($webct_courses)){
        $success = $webct->enroll_user($userid, $webct_courses);
        foreach ($webct_courses as $course)
            $course_codes[] = $course['ims_source']['id'];
        $course_codes = implode(", ", $course_codes);
        SimpleSAML_Logger::info("WebCT: Login '$userid' with courses: " .
            $course_codes);
        if ($success !== TRUE){
            $config = SimpleSAML_Configuration::getInstance();
            $t = new SimpleSAML_XHTML_Template($config,
                'webct:webct_warning.php');
            $t->data['warning'] = $success;
            $t->data['url'] = $url;
            $t->data['email'] = $config->getString('technicalcontact_email',
                NULL);
            $t->show();
            die;
        }
    }
} else {
    SimpleSAML_Logger::debug("WebCT: Reusing session for '$userid'.");
}

$url = $webct->get_sso_url($webct_uid);
header("Location: $url");
