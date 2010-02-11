<?php
/* Provision automatically a course for all people that login */

/* WebCT SSO and on-the-fly provisioning for simpleSAMLphp */
class MyWebCT_Connector extends sspmod_webct_Connector {
    function update_webct_courses($webct_courses, $userid){
        $webct_courses[] = array(
            'ims_source' => array(
                'source' => 'WebCT',
                'id'     => 'CAV_ALUMNOS.default',
            ),
            'status' => '1',
            'role' => '01',
        );
        return $webct_courses;
    }
}

$webct = new MyWebCT_Connector;
$webct->main();

