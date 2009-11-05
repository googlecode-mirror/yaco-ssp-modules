<?php

class sspmod_certvalidator_Utilities {

	private static function opensslExec($command, $certificate, $options) {
		assert('is_string($command)');
		assert('is_string($certificate)');
		assert('is_array($options)');

		$cmdarray = array(
			'openssl', $command);

		$cmdline = '';
		foreach($cmdarray as $c) {
			$cmdline .= escapeshellarg($c) . ' ';
		}

		foreach($options as $c) {
			$cmdline .= escapeshellarg($c) . ' ';
		}

		$cmdline .= '2>&1';
		$descSpec = array(
			0 => array('pipe', 'r'),
			1 => array('pipe', 'w'),
			);
		$process = proc_open($cmdline, $descSpec, $pipes);
		if (!is_resource($process)) {
			throw new Exception('Failed to execute openssl command: ' . $cmdline);
		}

		if (fwrite($pipes[0], $certificate) === FALSE) {
			throw new Exception('Failed to write certificate for pipe.');
		}
		fclose($pipes[0]);

		$out = '';
		while (!feof($pipes[1])) {
			$line = trim(fgets($pipes[1]));
			if(strlen($line) > 0) {
				$out .= $line . '\n';
			}
		}
		fclose($pipes[1]);

		$status = proc_close($process);
		return array($status, $out);
	}

	public static function convertCRL($crl_data, $crlpath, $filename) {
		assert('is_string($crl_data)');
		assert('is_string($crlpath)');
		assert('is_string($filename)');
		$cmdoptions = array('-inform', 'DER', '-outform', 'PEM', '-out', $crlpath.'/'.$filename);
		$result = self::opensslExec('crl', $crl_data, $cmdoptions);
		if($result[0]) {
			$cmdoptions = array('-inform', 'PEM', '-outform', 'PEM', '-out', $crlpath.'/'.$filename);
			$result = self::opensslExec('crl', $crl_data, $cmdoptions);
		}
		return $result;
	}

	public static function rehash($crl_data, $crlpath, $filename) {
		assert('is_string($crl_data)');
		assert('is_string($crlpath)');
		assert('is_string($filename)');
		$cmdoptions = array('-hash', '-noout');
		$result = self::opensslExec('crl', $crl_data, $cmdoptions);
		return $result;
	}

	public static function validateCertificate($certificate, $capath, $crlpath, $crl_check) {
		assert('is_string($certificate)');
		assert('is_string($capath)');
		assert('is_string($crlpath)');

		if (!is_dir($capath)) {
			throw new Exception('Could not find CAs dir: ' . $capath);
		}

		if (!is_dir($crlpath)) {
			throw new Exception('Could not find CRLs dir: ' . $crlpath);
		}

		$cmdoptions = array(
			'-CApath', $capath.':'.$crlpath,
			);
		if($crl_check) {
		    $cmdoptions[] = '-crl_check';
		}
		$result = self::opensslExec('verify', $certificate, $cmdoptions);
		if ($result[0] !== 0 || $result[1] !== 'stdin: OK\n') {
			return array(False,self::parseVerifyError($result[1]));
		}

		return array(True,'');
	}

    public static function validateCertificateWithCRLs($certificate, $capath, $crlpath, $crl_check) {
        return $this->validateCertificate($certificate, $capath, $crlpath, true);
    }

	public static function parseVerifyError($output) {
		assert('is_string($output)');

		if(!preg_match('/^stdin:/', $output)) {
			return 'unable_to_load';
		}
		if(preg_match('/error ([0-9]+) at/', $output, $matches)) {
			return 'error_found_' . $matches[1];
		}
		return 'error';
	}


	public static function getAttributesFromCert($certificate, &$attributes) {
		assert('is_string($certificate)');
		assert('is_array($attributes)');

		$cmdoptions = array('-noout', '-subject', '-issuer');
		$result = self::opensslExec('x509', $certificate, $cmdoptions);
		if ($result[0] == 0) {
			foreach(explode('\n', $result[1]) as $value) {
				if ($value) {
					$attr = explode('= ', $value, 2);
					$attributes[$attr[0]] = array($attr[1]);
				}
			}

		}
	}
}

?>