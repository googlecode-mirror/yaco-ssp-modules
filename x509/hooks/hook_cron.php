<?php
/**
 * Hook to run a cron job.
 *
 * @param array &$croninfo  Output
 */
function x509_hook_cron(&$croninfo) {
	assert('is_array($croninfo)');
	assert('array_key_exists("summary", $croninfo)');
	assert('array_key_exists("tag", $croninfo)');

	SimpleSAML_Logger::info('cron [certvalidator]: Running cron in cron tag [' . $croninfo['tag'] . '] ');

	try {
		$config = SimpleSAML_Configuration::getInstance();
		$certvalidator_config = SimpleSAML_Configuration::getConfig('config-certvalidator.php');
		$cron_config = SimpleSAML_Configuration::getConfig('module_cron.php');

		$cron_tags = $certvalidator_config->getArray('cron', array());

		$crlurl_array = $certvalidator_config->getArray('crlurl', array());
		$crlpath = $certvalidator_config->getValue('crlpath', array());

		if (in_array($croninfo['tag'], $cron_tags)) {
			$pattern = array('https://','http://','/','.der','.pem');
			$replace = array('','','-','','');
			foreach($crlurl_array as $crlurl) {
				$filename = str_replace($pattern, $replace, $crlurl);
				$filename .= '.pem';
				$crl_data = file_get_contents($crlurl);
				if(!$crl_data) {
					$croninfo['summary'][] = 'Error when accesing the url: '.$crlurl;
					continue;
				}
				$result = sspmod_x509_Utilities::convertCRL($crl_data, $crlpath, $filename);
				if($result[0]) {
					$croninfo['summary'][] = 'Error procesing CRL (convert to PEM): '.$crlurl;
					continue;
				}
				$crl_data = file_get_contents($crlpath.'/'.$filename);
				$result = sspmod_x509_Utilities::rehash($crl_data, $crlpath, $filename);
				if($result[0]) {
					$croninfo['summary'][] = 'Error procesing CRL (obtaining hash): '.$filename;
					continue;
				}
				else {
					$hash = substr($result[1], 0, 8);
					exec('ln -s '.$crlpath.'/'.$filename.' '.$crlpath.'/'.$hash.'.r0');
					$croninfo['summary'][] = 'Updated crl from: '.$crlurl;
				}
			}
			
		}

	} catch (Exception $e) {
		$croninfo['summary'][] = 'Error during certvalidator sync crl list: ' . $e->getMessage();
	}
}
?>
