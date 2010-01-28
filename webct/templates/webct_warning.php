<?php
/**
 * Warning template
 *
 * Parameters:
 * - 'warning': Warning.
 * - 'url': Target URL.
 *
 * @package simpleSAMLphp
 * @version $Id$
 */


$header = $this->t('{webct:webct:warning_header}');
$generic_text = $this->t('{webct:webct:warning_text}');
$continue_text = $this->t('{webct:webct:warning_continue}');

$warning = $this->data['warning'];
$url = $this->data['url'];

$this->includeAtTemplateBase('includes/header.php');
echo "<h1>$header</h1>\n";
echo "<p>$generic_text:</p>\n<br />\n";
echo "<p><b>$warning</b></p>\n<br />\n";
echo "<h3><center><a href=\"$url\">$continue_text</a></center></h3>\n";
$this->includeAtTemplateBase('includes/footer.php');
