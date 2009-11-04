<?php

class sspmod_x509auth_Utilities {

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


	public static function validateCertificateWithCRLs($certificate, $capath, $crlpath) {
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
			'-crl_check',
			);
		$result = self::opensslExec('verify', $certificate, $cmdoptions);
                if ($result[0] !== 0 || $result[1] !== 'stdin: OK\n') {
                        return array(False,self::parseVerifyError($result[1]));
                }

		return array(True,'');
	}

	public static function validateCertificateWithOCP($certificate, $capath, $ocpurl, $issuer) {
		assert('is_string($certificate)');
		assert('is_string($capath)');
		assert('is_string($ocpurl)');

		if (!is_dir($capath)) {
			throw new Exception('Could not find CAs dir: ' . $capath);
		}

		$issuer_cert_path = tempnam('/tmp', '');
		file_put_contents($issuer_cert_path, $issuer);
		$cmdoptions = array(
			'-CApath', $capath,
			'-url', $ocpurl,
			'-issuer', $issuer_cert_path,
			'-noverify', // WARNING
			'-cert', '/dev/stdin',
			);
		$result = self::opensslExec('ocsp', $certificate, $cmdoptions);
		unlink($issuer_cert_path);
                if ($result[0] !== 0 || preg_match('/\/dev\/stdin: good.*/', $result[1]) === 0) {
		    return array(False,self::parseVerifyError($result[1]));
                }

		return array(True, '');
	}


	public static function parseVerifyError($output) {
		assert('is_string($output)');

		if(!preg_match('/^stdin:/', $output)) {
			return 'unable_to_load';
		}
		if(preg_match('/error ([0-9]+) at/', $output, $matches)) {
			return 'error_found_' . $matches[1];
		}
		return 'patata';
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
