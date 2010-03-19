<?php

class sspmod_attributevalidator_Auth_Process_ValidationFilter extends SimpleSAML_Auth_ProcessingFilter {

	private $check;
	private $required_attrs;
	private $recommended_attrs;
	private $optional_attrs;
	private $generated_attrs;
	private $format_validation;
	private $validation_config;

	/**
	 * Initialize this filter.
	 *
	 * @param array $config  Configuration information about this filter.
	 * @param mixed $reserved  For future use.
	 */
	public function __construct($config, $reserved) {
		parent::__construct($config, $reserved);

		if(!isset($config['check'])) {
            throw new SimpleSAML_Error_Exception($this->authId . ': \'check\' config parameter seems to be missing.');
        }
		$this->check = $config['check'];		

		try {
			$validation_config = SimpleSAML_Configuration::getInstance();
			$validation_config_file = 'config-attributevalidator.php';
			$this->validation_config = $validation_config->copyFromBase('attributesvalidator', $validation_config_file);
		}
		catch (Exception $e) {
			throw new SimpleSAML_Error_Exception($this->authId . ': Missing attributesvalidator config file: '. $validation_config_file);
		}

		$this->required_attrs = $this->validation_config->getValue('required_attrs', array());
		$this->recommended_attrs = $this->validation_config->getValue('recommended_attrs', array());
		$this->optional_attrs = $this->validation_config->getValue('optional_attrs', array());
		$this->generated_attrs = $this->validation_config->getValue('generated_attrs', array());
		$this->format_validation_regex = $this->validation_config->getValue('format_validation_regex', array());

		foreach ($this->check as $attrset_to_check) {
			if (!isset($this->$attrset_to_check)) {
				throw new SimpleSAML_Error_Exception($this->authId . ': Wrong value of \'check\' parameter, not exist the set: '.$attrset_to_check);
			}
		}
    }

	
	/**
	* Validate attributes.
	*
	* @param array &$request  The current request
	*/
	public function process(&$request) {
		$attributes = $request['Attributes'];
		$emptys = array();
		$invalids = array();
	
		foreach ($this->check as $set_to_check) {
			$attrset_to_check = $this->$set_to_check;
			foreach($attrset_to_check as $attr_name) {
				if(empty($attributes[$attr_name])) {
					$emptys[] = $attr_name;
				}
				else {
					if(in_array($attr_name, array_keys($this->format_validation_regex))) {
						$value = $attributes[$attr_name];
						$regex = $this->format_validation_regex[$attr_name];
						if(!is_array($value)) {
							$attrs_to_valid = array($value);    
						}
						else {
							$attrs_to_valid = $value;
						}
						foreach($attrs_to_valid as $attr_to_valid) {
							if(!@preg_match($regex, $attr_to_valid, $val)) {
								if(!isset($invalids[$attr_name]['regex'])) {				
									$invalids[$attr_name]['regex'] = $regex;
									$invalids[$attr_name]['values'] = array();
								}
								$invalids[$attr_name]['value'][] = $attr_to_valid;
							}
						}
					}
				}
			}
		}
		$original_source_entity_id = '';
		if(isset($request['Attributes']['original_source_entity_id'])) {
			$original_source_entity_id = $request['Attributes']['original_source_entity_id'][0];
			unset($request['Attributes']['original_source_entity_id']);
		}

		if(!empty($emptys) || !empty($invalids)) {

			$session = SimpleSAML_Session::getInstance();
			$trackid = $session->getTrackId();
			$error_code = 'SSOSERVICEPARAMS';
			SimpleSAML_Logger::error($_SERVER['PHP_SELF'].' - UserError: ErrCode:' . $error_code. ' Empty or invalid required attributes');
			SimpleSAML_Logger::error('Empty required attributes: '.implode(', ',$emptys)
									. ' Invalid required attributes: '.implode(', ', array_keys($invalids)));
			SimpleSAML_Logger::error('User attributes: ' . eregi_replace("[\n|\r|\n\r]", ' ', var_export($request['Attributes'], true)));
			SimpleSAML_Logger::error('From: ' . (!empty($original_source_entity_id) ? $original_source_entity_id: 'unknown')
									. ' To: '.$request['Destination']['entityid']);
			$config = SimpleSAML_Configuration::getInstance();
			if(!empty($original_source_entity_id)) {
				$t = new SimpleSAML_XHTML_Template($config, 'attributevalidator:errorreport_to_idp.php', 'errors');
				$t->data['error_report_idp_url'] = str_replace('saml2/idp/metadata.php', 'errorreport.php', $original_source_entity_id);
				$t->data['trackid'] = $trackid;
			}
			else {
				$t = new SimpleSAML_XHTML_Template($config, 'error.php', 'errors');
				$baseurl = SimpleSAML_Utilities::selfURLhost() . '/' . $config->getBaseURL();
				$t->data['errorreportaddress'] = $baseurl . 'errorreport.php';				
			}

			$t->includeInlineTranslation('empty_or_invalid_attrs', array (
																	'en' => 'Empty or invalid required attributes:',
																	'es' => 'Existen atributos requeridos que estan vacios o su valor es inválido',
										));
			$t->includeInlineTranslation('empty_attrs', array (
																	'en' => 'Empty required attributes:',
																	'es' => 'Atributos requeridos vacios:',
										));
			$t->includeInlineTranslation('invalid_attrs', array (
																	'en' => 'Invalid required attributes:',
																	'es' => 'Atributos requeridos inválidos:',
										));
			$t->includeInlineTranslation('user_attrs', array (
																	'en' => 'User attributos:',
																	'es' => 'Atributos del usuario:'
										));

			$t->data['errorcode'] = $error_code;
			$t->data['showerrors'] = TRUE;
			$t->data['version'] = $config->getValue('version', '');
			$t->data['email'] = '';
			if(array_key_exists('irisMailMainAddress', $request['Attributes']) && !empty($request['Attributes']['irisMailMainAddress'])) {
				$t->data['email'] = $request['Attributes']['irisMailMainAddress'][0];
			}
			else if(array_key_exists('mail', $request['Attributes']) && !empty($request['Attributes']['mail'])) {
				$t->data['email'] = $request['Attributes']['mail'][0];
			}
			$exceptionmsg = '<p><b>'.$t->t('empty_or_invalid_attrs').'</b> </p>'."\r\n";
			$t->data['exceptionmsg'] = $exceptionmsg;		
			$exceptiontrace = '<p><b>'.$t->t('empty_attrs').'</b> ';
			$exceptiontrace .= implode(", ", $emptys).'</p>'."\n"."\r\n";
			$exceptiontrace.= '<p><b>'.$t->t('invalid_attrs').'</b> ';	
			$t->data['user_exceptionmsg'] = $exceptionmsg .$exceptiontrace .htmlentities(implode(', ', array_keys($invalids))).'</p>'."\r\n";
			if(empty($original_source_entity_id)) {				
				$t->data['exceptiontrace'] = strip_tags($t->data['user_exceptionmsg']);
				$t->data['exceptionmsg'] = strip_tags($t->data['exceptionmsg']);
			}
			else {
				$t->data['exceptiontrace'] = $exceptiontrace;			
				$t->data['exceptiontrace'] .= '<ul>'."\r\n";
				foreach ($invalids as $name => $invalid) {
					$t->data['exceptiontrace'] .= "<li>'".htmlentities($name)."' -->\r\n\t";
					$t->data['exceptiontrace'] .= implode(", \r\n\t", $invalid['value']).'</li>'."\r\n";		
				}
				$t->data['exceptiontrace'] .= '</ul></p>'."\r\n";
				$t->data['exceptiontrace'] .= '<p><b>'.$t->t('user_attrs').'</b> '.var_export($request['Attributes'], true).'</p>';
				$t->data['url'] = 'Error detected at: '.$_SERVER['HTTP_HOST'];
			}
			
			$t->show();
			exit; 
		}
	}
}
?>
