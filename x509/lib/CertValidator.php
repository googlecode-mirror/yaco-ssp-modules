<?php

class sspmod_x509_CertValidator {

	private static $capath;
	private static $crlpath;


	/**
	 * Constructor for this authentication source.
	 *
	 * @param array $info  Information about this authentication source.
	 * @param array $config  Configuration.
	 */
	public function __construct($info, $config) {
		assert('is_array($info)');
		assert('is_array($confi)');
		assert('array_key_exists("capath", $config)');
		assert('array_key_exists("crlpath", $config)');

		/* Call the parent constructor first, as required by the interface. */
		parent::__construct($info, $config);

		$this->capath = $config['capath'];
		$this->crlpath = $config['crlpath'];
	}


	public static function validateCert($pem) {
		$config = SimpleSAML_Configuration::getInstance();
		$autoconfig = $config->copyFromBase('certvalidator', 'config-certvalidator.php');

		$capath = $autoconfig->getValue('capath');
		$crlpath = $autoconfig->getValue('crlpath');
		$crl_check = $autoconfig->getBoolean('crl_check', FALSE);

			if(strpos($pem, "-----BEGIN CERTIFICATE-----") === FALSE) {
$pem = <<<CERTEOT
-----BEGIN CERTIFICATE-----
$pem
-----END CERTIFICATE-----
CERTEOT;
			}

		$result = sspmod_certvalidator_Utilities::validateCertificate($pem, $capath, $crlpath, $crl_check);

		if($result[0]) {
			return "cert_validation_success";
		} else {
			return $result[1];
		}
	}

}


?>
