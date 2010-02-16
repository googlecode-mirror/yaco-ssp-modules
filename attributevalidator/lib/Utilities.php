<?php

class sspmod_attributevalidator_Utilities {

	private static function &filterPriority(&$attrs, $priority_list, &$all_present=False) {
		$filtered_list = array();
		foreach($priority_list as $name) {
			if(array_key_exists($name, $attrs)) {
				$value = $attrs[$name];
				$missing = False;
				unset($attrs[$name]);
			} else {
			    $value = "";
				$missing = True;
				$all_present = False;
			}
			$filtered_list[$name] = array(
				"value" => $value,
				"missing" => $missing,
				);
		}
		return $filtered_list;
	}

	public static function validateAttributes($attrs, $required_attrs, $recommended_attrs, $optional_attrs, $generated_attrs) {
		assert(is_array($attrs));
		assert(is_array($required_attrs));
		assert(is_array($recommended_attrs));
		assert(is_array($optional_attrs));
		assert(is_array($generated_attrs));

		$validates = True; // ends up True if all required attributes are present

		$required = self::filterPriority($attrs, $required_attrs, $validates);
		$recommended = self::filterPriority($attrs, $recommended_attrs);
		$optional = self::filterPriority($attrs, $optional_attrs);
		$generated = self::filterPriority($attrs, $generated_attrs);
		$unknown = $attrs;

		return array($required, $recommended, $optional, $generated, $unknown, $validates);
	}
}

?>
