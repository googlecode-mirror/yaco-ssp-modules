<?php
/**
 * 403 error template
 *
 * Parameters:
 * - 'target': Target URL.
 * - 'params': Parameters which should be included in the request.
 *
 * @package simpleSAMLphp
 * @version $Id$
 */


$header = $this->t('{webct:webct:403_header}');
$text = $this->t('{webct:webct:403_text}', $this->data);
$this->includeAtTemplateBase('includes/header.php');
?>
<h1><?php echo $header; ?></h1>
<p><?php echo $text; ?></p>
<?php
$this->includeAtTemplateBase('includes/footer.php');
?>
