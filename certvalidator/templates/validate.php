<?php
	$this->includeAtTemplateBase('includes/header.php');
	if (isset($this->data['error'])) { ?>
		<div style="border-left: 1px solid #e8e8e8; border-bottom: 1px solid #e8e8e8; background: #f5f5f5">
		<img src="/<?php echo $this->data['baseurlpath']; ?>resources/icons/bomb.png" style="float: left; margin: 15px " />
		<h2><?php echo $this->t('error_header'); ?></h2>

		<p><?php echo $this->t($this->data['error']); ?> </p>
		</div>
	<?php }

	if (isset($this->data['success'])) { ?>
		<div style="border-left: 1px solid #e8e8e8; border-bottom: 1px solid #e8e8e8; background: #f5f5f5">
		<img src="/<?php echo $this->data['baseurlpath']; ?>resources/icons/star.png" style="float: left; margin: 0px 15px " />
		<h2><?php echo $this->t('success_header'); ?></h2>

		<p><?php echo $this->t($this->data['success']); ?> </p>
		</div>
	<?php } ?>


	<h2 style="break: both"><?php echo $this->t('user_CV_header'); ?></h2>

	<p><?php echo $this->t('user_CV_text'); ?></p>

	<form name="ctl00" id="ctl00" method="post" action="">
			<p>
				<textarea name="x509Cert" cols="80" rows="10"><?php echo $this->data['x509Cert']; ?></textarea>
			</p>
			<p>
				<input type="submit" value="<?php echo $this->t('user_CV_submit'); ?>" />
				<input type="button" name="reset" value="reset" onClick="javascript:{document.ctl00.x509Cert.value='';}">
			</p>
	</form>


<?php $this->includeAtTemplateBase('includes/footer.php'); ?>
