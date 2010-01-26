<?php
/**
 * Frontpage hook for webct
 *
 * @param array &$links The links on the frontpage, split into sections
 *
 * @return void
 *
 * @since Function available since Release 1.0.0
 */
function webct_Hook_frontpage(&$links)
{
	assert('is_array($links)');

	$links['welcome'][] = array(
		'href' => SimpleSAML_Module::getModuleURL('webct/login.php'),
		'text' => array('en' => 'Login to Blackboard/WebCT',
                        'es' => 'Entrada a Blackboard/WebCT',
        ),
        'shorttext' => array('en' => 'BB/WebCT'),
	);
}
?>
