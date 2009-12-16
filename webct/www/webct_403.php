<?php
/**
 * Show a 403 Forbidden page about not authorized to access this application.
 *
 * @package simpleSAMLphp
 * @version $Id$
 */

$config = SimpleSAML_Configuration::getInstance();
$t = new SimpleSAML_XHTML_Template($config, 'webct:webct_403.php');
header('HTTP/1.0 403 Forbidden');
$t->show();
