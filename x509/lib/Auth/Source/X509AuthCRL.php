<?php

class sspmod_x509_Auth_Source_X509AuthCRL extends sspmod_x509_Auth_Source_X509Auth {

	private $crlpath;

	public function __construct($info, $config) {
		assert('is_array($info)');
		assert('is_array($config)');
		assert('array_key_exists("crlpath", $config)');

		parent::__construct($info, $config);

		$this->crlpath = $config['crlpath'];
	}

	public function validateCertificate($cert) {
		return sspmod_x509_Utilities::validateCertificateWithCRL(
			$cert, $this->capath, $this->crlpath
		);
	}
}

?>