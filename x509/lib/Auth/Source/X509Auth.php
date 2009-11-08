<?php

class sspmod_x509_Auth_Source_X509Auth extends SimpleSAML_Auth_Source {

	protected $capath;
	private $attributesmap;

	//The string used to identify our states.
	const STAGEID = 'sspmod_core_Auth_UserPassBase.state';

	//The key of the AuthId field in the state.
	const AUTHID = 'sspmod_core_Auth_UserPassBase.AuthId';

	/**
	 * Constructor for this authentication source.
	 *
	 * @param array $info  Information about this authentication source.
	 * @param array $config	 Configuration.
	 */
	public function __construct($info, $config) {
		assert('is_array($info)');
		assert('is_array($config)');
		assert('array_key_exists("capath", $config)');

		/* Call the parent constructor first, as required by the interface. */
		parent::__construct($info, $config);

		$this->capath = $config['capath'];

		if (array_key_exists("attributesmap", $config)) {
			$this->attributesmap = $config['attributesmap'];
		} else {
			$this->attributesmap = array();
		}
	}

	public function authenticate(&$state) {
		assert('is_array($state)');

		/* We are going to need the authId in order to retrieve this authentication source later. */
		$state[self::AUTHID] = $this->authId;
		$id = SimpleSAML_Auth_State::saveState($state, self::STAGEID);
		$url = SimpleSAML_Module::getModuleURL('x509/login.php');
		SimpleSAML_Utilities::redirect($url, array('AuthState' => $id));
	}

	public static function handleLogin($authStateId, $cert) {
		assert('is_string($authStateId)');
		assert('is_string($cert)');

		$state = SimpleSAML_Auth_State::loadState($authStateId, self::STAGEID);
		assert('array_key_exists(self::AUTHID, $state)');

		$source = SimpleSAML_Auth_Source::getById($state[self::AUTHID]);
		if ($source === NULL) {
			throw new Exception('Could not find authentication source with id ' . $state[self::AUTHID]);
		}

		$result = $source->validateCertificate($cert);

		if($result[0]) {
			$attributes = array();
			sspmod_x509_Utilities::getAttributesFromCert($cert, $attributes);
			foreach($source->attributesmap as $src => $dst) {
				if (array_key_exists($src, $attributes)) {
					$attributes[$dst] = $attributes[$src];
				}
			}
			$state['Attributes'] = $attributes;
			SimpleSAML_Auth_Source::completeAuth($state);
		} else {
			return $result[1];
		}
	}
}
?>