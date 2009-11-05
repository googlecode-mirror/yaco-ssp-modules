<?php
/**
 * Frontpage hook for Certvalidator 
 *
 * This hook adds a link to JANUS to the frontapage of the local SimpleSAMLphp 
 * installation. 
 *
 * @param array &$links The links on the frontpage, split into sections
 *
 * @return void
 *
 * @since Function available since Release 1.0.0
 */
function CertValidator_Hook_frontpage(&$links)
{
    assert('is_array($links)');

    $links['federation'][] = array(
        'href' => SimpleSAML_Module::getModuleURL('certvalidator/validate.php'),
        'text' => array('en' => 'Certvalidator module'),
    );
}
?>
