<?php
	$this->includeAtTemplateBase('includes/header.php'); 
	if (!array_key_exists('icon', $this->data)) $this->data['icon'] = 'lock.png';
	if (isset($this->data['error'])) { ?>
		<div style="border-left: 1px solid #e8e8e8; border-bottom: 1px solid #e8e8e8; background: #f5f5f5">
		<img src="/<?php echo $this->data['baseurlpath']; ?>resources/icons/bomb.png" style="float: left; margin: 15px " />
		<h2><?php echo $this->t('error_header'); ?></h2>
		
		<p><?php echo $this->t($this->data['error']); ?> </p>
		</div>
	<?php } ?>

	
<?php $this->includeAtTemplateBase('includes/footer.php'); ?>
