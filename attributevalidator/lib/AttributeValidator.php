<?php

class sspmod_attributevalidator_AttributeValidator {

	/**
	 * Constructor for this authentication source.
	 *
	 * @param array $info  Information about this authentication source.
	 * @param array $config  Configuration.
	 */
	public function __construct($info, $config) {
		assert('is_array($info)');
		assert('is_array($config)');
		assert('array_key_exists("attributes", $config)');

		/* Call the parent constructor first, as required by the interface. */
		parent::__construct($info, $config);
	}


    public static function validateAttributes($attributes) {
        $config = SimpleSAML_Configuration::getInstance();
        $autoconfig = $config->copyFromBase('attributesvalidator', 'config-attributevalidator.php');

		$required_attrs = $autoconfig->getValue('required_attrs');
		$recommended_attrs = $autoconfig->getValue('recommended_attrs');
		$optional_attrs = $autoconfig->getValue('optional_attrs');

        $filtered_attributes = sspmod_attributevalidator_Utilities::validateAttributes($attributes, $required_attrs, $recommended_attrs, $optional_attrs);

        return $filtered_attributes;
    }

}

?>