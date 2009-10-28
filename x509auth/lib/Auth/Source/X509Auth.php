<?php

class sspmod_x509auth_Auth_Source_X509Auth extends SimpleSAML_Auth_Source {

	/**
	 * A LDAP configuration object.
	 */
	private static $capath;
	private static $crlpath;

        //The string used to identify our states.
        const STAGEID = 'sspmod_core_Auth_UserPassBase.state';


        //The key of the AuthId field in the state.
        const AUTHID = 'sspmod_core_Auth_UserPassBase.AuthId';


	/**
	 * Constructor for this authentication source.
	 *
	 * @param array $info  Information about this authentication source.
	 * @param array $config  Configuration.
	 */
	public function __construct($info, $config) {
		assert('is_array($info)');
		assert('is_array($config)');
		assert('array_key_exists("capath", $config)');
		assert('array_key_exists("crlpath", $config)');

		/* Call the parent constructor first, as required by the interface. */
		parent::__construct($info, $config);

		$this->capath = $config['capath'];
		$this->crlpath = $config['crlpath'];
	}


        public function authenticate(&$state) {
                assert('is_array($state)');

                /* We are going to need the authId in order to retrieve this authentication source later. */
                $state[self::AUTHID] = $this->authId;
                $id = SimpleSAML_Auth_State::saveState($state, self::STAGEID);
                $url = SimpleSAML_Module::getModuleURL('x509auth/login.php');
                SimpleSAML_Utilities::redirect($url, array('AuthState' => $id));
        }


       public static function handleLogin($authStateId, $x509) {
                assert('is_string($authStateId)');
		assert('openssl_x509_export($x509, &$pem)');
		SimpleSAML_Logger::notice('+++++++++++++++'. $pem);
		assert('is_string($pem)');

                $config = SimpleSAML_Configuration::getInstance();

		$state = SimpleSAML_Auth_State::loadState($authStateId, self::STAGEID);
		assert('array_key_exists(self::AUTHID, $state)');

		$source = SimpleSAML_Auth_Source::getById($state[self::AUTHID]);
		if ($source === NULL) {
			throw new Exception('Could not find authentication source with id ' . $state[self::AUTHID]);
		}

                $capath = $source->capath;
                $crlpath = $source->crlpath;
                $result = sspmod_x509auth_Utilities::validateCertificateWithCRLs($pem, $capath, $crlpath);

                if($result[0]) {
                        $attributes = array();
			sspmod_x509auth_Utilities::getAttributesFromCert($pem, $attributes);
                        $state['Attributes'] = $attributes;
                        SimpleSAML_Auth_Source::completeAuth($state);
                } else {
                        return $result[1];
                }
        }


}


?>
