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


	public static function validateCert($cert, $crl_validation=true, $ocsp_validation=false) {
		$config = SimpleSAML_Configuration::getInstance();
		$autoconfig = $config->copyFromBase('certvalidator', 'config-certvalidator.php');

		$capath = $autoconfig->getValue('capath');

		if(strpos($cert, "-----BEGIN CERTIFICATE-----") === FALSE) {
$cert = <<<CERTEOT
-----BEGIN CERTIFICATE-----
$cert
-----END CERTIFICATE-----
CERTEOT;
		}
		if ($crl_validation) {
			$crlpath = $autoconfig->getValue('crlpath');
			$result = sspmod_x509_Utilities::validateCertificateWithCRL($cert, $capath, $crlpath);
		} else if ($ocsp_validation) {
			$ocspurl = $autoconfig->getValue('ocspurl');
			$issuer = file_get_contents($autoconfig->getValue('ocspissuer'));
			$result = sspmod_x509_Utilities::validateCertificateWithOCSP($cert, $capath, $ocspurl, $issuer);
		} else {
			$result = sspmod_x509_Utilities::validateCertificate($cert, $capath);
		}

		if($result[0]) {
			return "cert_validation_success";
		} else {
			return $result[1];
		}
	}
	
	public static function getDaysUntilExpiration($cert) {
		if(strpos($cert, "-----BEGIN CERTIFICATE-----") === FALSE) {
$cert = <<<CERTEOT
-----BEGIN CERTIFICATE-----
$cert
-----END CERTIFICATE-----
CERTEOT;
		}
		try {
			return sspmod_x509_Utilities::getDaysUntilExpiration($cert);
		}
		catch (Exception $e){
			return -1;
		}
	}
}
?>
