<?php

class sspmod_attributevalidator_Utilities {

	private static function &filterPriority(&$attrs, $format_validation_regex, $priority_list, &$all_valid=False) {
		$filtered_list = array();
		foreach($priority_list as $name) {
			$invalid_format_indexes = array();
			$regex = '';
			if(array_key_exists($name, $attrs)) {
				if(empty($attrs[$name])) {
					$value = "";
					$valid = 'not_found';
					$all_valid = False;         
				}
				else {
					$value = $attrs[$name];
					$valid = 'valid';
					if(array_key_exists($name, $format_validation_regex)) {
						$regex = $format_validation_regex[$name];
						if(!is_array($value)) {
							$attrs_to_valid = array($value);    
						}
						else {
							$attrs_to_valid = $value;
						}
						foreach($attrs_to_valid as $index => $attr_to_valid) {
							if(!@preg_match($regex, $attr_to_valid, $values)) {
								$valid = 'invalid_format';
								$all_valid = False;
								$invalid_format_indexes[] = $index;
							}
						}
					}
				}
				unset($attrs[$name]);
			} 
			else {
				$value = "";
				$valid = 'not_found';
				$all_valid = False;
			}
			$filtered_list[$name] = array(
				"value" => $value,
				"valid" => $valid,
				"invalid_format_indexes" => $invalid_format_indexes,
				"regex" => $regex,
				);
		}
		return $filtered_list;
	}

	public static function validateAttributes($attrs, $format_validation_regex, $required_attrs, $recommended_attrs, $optional_attrs, $generated_attrs) {
		assert(is_array($attrs));
		assert(is_array($required_attrs));
		assert(is_array($recommended_attrs));
		assert(is_array($optional_attrs));
		assert(is_array($generated_attrs));
		assert(is_array($format_validation_regex));

		$validates = True; // ends up True if all required attributes are present

		$required = self::filterPriority($attrs, $format_validation_regex, $required_attrs, $validates);
		$recommended = self::filterPriority($attrs, $format_validation_regex, $recommended_attrs);
		$optional = self::filterPriority($attrs,$format_validation_regex, $optional_attrs);
		$generated = self::filterPriority($attrs,$format_validation_regex, $generated_attrs);
		$unknown = $attrs;

		return array($required, $recommended, $optional, $generated, $unknown, $validates);
	}
}

?>
