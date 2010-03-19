<?php 
	$this->data['header'] = $this->t('error_header');
	$this->data['icon'] = 'bomb_l.png';
	
	$this->data['head'] = '
<meta name="robots" content="noindex, nofollow" />
<meta name="googlebot" content="noarchive, nofollow" />';
	
	$this->includeAtTemplateBase('includes/header.php'); 
?>


	<h2><?php 
		echo $this->t('title_' . $this->data['errorcode']); 
	?></h2>

<?php
/* Print out exception only if the exception is available. */
if (array_key_exists('showerrors', $this->data) && $this->data['showerrors']) {
?>
		<h2><?php echo $this->t('debuginfo_header'); ?></h2>
		<p><?php echo $this->t('debuginfo_text'); ?></p>
		
		<div style="border: 1px solid #eee; padding: 1em;">
			<p style="margin: 1px"><?php echo $this->data['user_exceptionmsg']; ?></p>			
		</div>
<?php
}
?>

<?php
/* Add error report submit section if we have a valid technical contact. 'errorreportaddress' will only be set if
 * the technical contact email address has been set.
 */
if (!empty($this->data['error_report_idp_url'])) {
?>

	<h2><?php echo $this->t('report_header'); ?></h2>
	<form action="<?php echo htmlspecialchars($this->data['error_report_idp_url']); ?>" method="post">
	
		<p><?php echo $this->t('report_text'); ?></p>
			<p><?php echo $this->t('report_email'); ?> <input type="text" size="25" name="email" value="<?php echo($this->data['email']); ?>" />
			<p>
		<input type="hidden" name="version" value="<?php echo htmlspecialchars($this->data['version']); ?>" />
		<input type="hidden" name="exceptionmsg" value="<?php echo htmlspecialchars($this->data['exceptionmsg']); ?>" />
		<input type="hidden" name="exceptiontrace" value="<?php echo htmlspecialchars($this->data['exceptiontrace']); ?>" />
		<input type="hidden" name="errorcode" value="<?php echo htmlspecialchars($this->data['errorcode']); ?>" />
		<input type="hidden" name="url" value="<?php echo htmlspecialchars($this->data['url']); ?>" />
		<input type="submit" name="send" value="<?php echo $this->t('report_submit'); ?>" />
		</p>
	</form>
<?php
}
?>

<?php $this->includeAtTemplateBase('includes/footer.php'); ?>
