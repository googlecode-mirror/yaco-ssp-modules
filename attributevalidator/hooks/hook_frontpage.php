<?php
/**
 * Frontpage hook for AttributeValidator
 *
 * @param array &$links The links on the frontpage, split into sections
 *
 * @return void
 *
 * @since Function available since Release 1.0.0
 */
function AttributeValidator_Hook_frontpage(&$links)
{
	assert('is_array($links)');

	$links['federation'][] = array(
		'href' => SimpleSAML_Module::getModuleURL('attributevalidator/validate.php'),
		'text' => array('en' => 'Attribute validator module', 'es' => 'Módulo de validación de atributos'),
	);
}
?>