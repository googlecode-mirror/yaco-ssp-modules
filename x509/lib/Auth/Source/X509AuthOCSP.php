<?php

class sspmod_x509_Auth_Source_X509AuthOCSP extends sspmod_x509_Auth_Source_X509Auth {

	private $ocspurl;

	public function __construct($info, $config) {
		assert('is_array($info)');
		assert('is_array($config)');
		assert('array_key_exists("ocspurl", $config)');

		parent::__construct($info, $config);

		$this->ocspurl = $config['ocspurl'];
	}

	public function validateCertificate($cert) {
		$issuer = $_SERVER['SSL_CLIENT_CERT_CHAIN_0'];
		return sspmod_x509_Utilities::validateCertificateWithOCSP(
			$cert, $this->capath, $this->ocspurl, $issuer
		);
	}
}

?>