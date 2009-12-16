<?php

// requires SSP >= 1.5
$webct = new sspmod_webct_Connector;
$as = new SimpleSAML_Auth_Simple($webct->authsource);
$as->requireAuth();
$attributes = $as->getAttributes();


// Check if userid exists
if (!isset($attributes[$webct->userid_attr]))
    throw new Exception('User ID is missing');
$userid = $attributes[$webct->userid_attr][0];


$session = SimpleSAML_Session::getInstance();
// get SSO url. If we have it already, provisioning was done before
$url = $session->getData('WebCT_URL', 'url');
if (empty($url)){
    // get user enrollments
    $courses = $attributes[$webct->courses_enrollments_attr];
    // translate codes & filter out anything we won't provide
    $webct_courses = $webct->translate_course_array($courses);
    // check if there is any valid course enrollment
    if (empty($webct_courses))
        throw new Exception("No dispone de acceso a cursos " .
            "en esta plataforma!");

    // get automatic sign-on URL from WebCT for the user.
    $url = $webct->get_sso_url($userid);
    if ($url == FALSE){
        // if user doesn't exist, create it
        $res = $webct->create_user($userid, $attributes);
        if ($res == TRUE)
            $url = $webct->get_sso_url($userid);
        else
            throw new Exception("No se puede crear usuario "
                . "en esta plataforma: $userid !");
        // Store $url for the next time to avoid reprovisioning
        $session->setData('WebCT_URL', 'url', $url);
    }

    // enroll user in course sections
    $webct->enroll_user($userid, $webct_courses);
}

header("Location: $url");
