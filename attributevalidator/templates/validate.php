<?php

$this->data['head'] = '<link rel="stylesheet" type="text/css" href="/' . $this->data['baseurlpath'] . 'module.php/attributevalidator/css/attributevalidator.css" />';
$this->includeAtTemplateBase('includes/header.php');
?>

<h2><?php if (isset($this->data['header'])) { echo($this->data['header']); } else { echo($this->t('{status:some_error_occured}')); } ?></h2>

<?php
// consent style listng start

function present_list($attr) {
	if (is_array($attr) && count($attr) > 1) {
		$str = '<ul><li>' . join('</li><li>', $attr) . '</li></ul>';
		return $str;
	} else {
		return htmlspecialchars($attr[0]);
	}
}

function present_assoc($attr) {
	if (is_array($attr)) {
		$str = '<dl>';
		foreach ($attr AS $key => $value) {
			$str .= "\n" . '<dt>' . htmlspecialchars($key) . '</dt><dd>' . present_list($value) . '</dd>';
		}
		$str .= '</dl>';
		return $str;
	} else {
		return htmlspecialchars($attr);
	}
}

function present_attributes_list($t, $attributes) {
	$str = '<ul class="attributes">';
	foreach ($attributes as $name => $data) {
		$str .= '<li>' . $name . '</li>';
		$str .= "\n";
	}
	$str .= '</ul>';
	return $str;
}

function present_attributes_table($t, $attributes, $nameParent, $color_missing_rows=False) {
	$alternate = array('odd', 'even'); $i = 0;
	$parentStr = (strlen($nameParent) > 0)? strtolower($nameParent) . '_': '';
	$str = (strlen($nameParent) > 0)? '<table class="attributes">': '<table class="attributes table_with_attributes">';
	$str .= '<tr class="tableHeader"><th class="rightWhite">' . $t->t('table_present') . '</th><th class="rightWhite">' . $t->t('table_name') . '</th><th>' . $t->t('table_value') . '</th></tr>';
	$imgPath = '/' . $t->data['baseurlpath'] . 'module.php/attributevalidator/img/';
	foreach ($attributes as $name => $data) {
		$nameraw = $name;
		$value = $data["value"];
		$missingImg = $data["missing"] ? "delete" : "accept";
		$missingMsg = $data["missing"] ? "Atributo no encontrado" : "Atributo encontrado";
		$color_row = $data["missing"] && $color_missing_rows ? 'colorrow' : '';

		if (preg_match('/^child_/', $nameraw)) {
			$parentName = preg_replace('/^child_/', '', $nameraw);
			foreach($value AS $child) {
				$str .= '<tr class="odd"><td colspan="2" style="padding: 2em">' . present_attributes_table($t, $child, $parentName) . '</td></tr>';
			}
		} else {
			if (sizeof($value) > 1) {
				$str .= '<tr class="' . $alternate[($i++ % 2)] . ' ' . $missingImg . ' ' . $color_row . '"><td class="attrmissing rightWhite"><img src="' . $imgPath . $missingImg . '.png" alt="' . $missingMsg . '"/></td><td class="attrname  rightWhite">' . htmlspecialchars($name) . '</td><td class="attrvalue"><ul>';
				foreach ($value AS $listitem) {
					if ($nameraw === 'jpegPhoto') {
						$str .= '<li><img src="data:image/jpeg;base64,' . $listitem . '" /></li>';
					} else {
						$str .= '<li>' . present_assoc($listitem) . '</li>';
					}
				}
				$str .= '</ul></td></tr>';
			} else {
				if(is_array($value)) {
					$value = array_shift($value);
				}
				$str .= '<tr class="' . $alternate[($i++ % 2)] . ' ' . $missingImg . ' ' . $color_row . '"><td class="attrmissing rightWhite"><img src="'. $imgPath . $missingImg . '.png" alt="' . $missingMsg . '" /></td><td class="attrname rightWhite">' . htmlspecialchars($name) . '</td>';
				if ($nameraw === 'jpegPhoto') {
					$str .= '<td class="attrvalue"><img src="data:image/jpeg;base64,' . htmlspecialchars($value) . '" /></td></tr>';
				} else {
					$str .= '<td class="attrvalue">' . htmlspecialchars($value) . '</td></tr>';
				}
			}
		}
		$str .= "\n";
	}
	$str .= '</table>';
	return $str;
}

$attributes = $this->data['attributes'];
// attributes should have been processed by sspmod_attributevalidator_AttributeValidator::validateAttributes
$required_attrs = $attributes[0];
$recommended_attrs = $attributes[1];
$optional_attrs = $attributes[2];
$generated_attrs = $attributes[3];
$unknown_attrs = $attributes[4];
$validates = $attributes[5];

if($validates) {
	echo('<span class="messageOk">' . $this->t('attributes_ok') . '</span>');
} else {
	echo('<span class="messageBad">' . $this->t('attributes_bad') . '</span>');
}
?>

<h2><?php echo $this->t('required_attrs_header'); ?></h2>
<?php
echo(present_attributes_table($this, $required_attrs, '', True));
?>

<h2><?php echo $this->t('recommended_attrs_header'); ?></h2>
<?php
echo(present_attributes_table($this, $recommended_attrs, ''));
?>

<h2><?php echo $this->t('optional_attrs_header'); ?></h2>
<?php
echo(present_attributes_table($this, $optional_attrs, ''));
?>

<h2><?php echo $this->t('generated_attrs_header'); ?></h2>
<?php
echo(present_attributes_table($this, $generated_attrs, ''));
?>

<h2><?php echo $this->t('unknown_attrs_header'); ?></h2>
<?php
echo(present_attributes_list($this, $unknown_attrs));

if (isset($this->data['logout'])) {
	echo('<h2>' . $this->t('{status:logout}') . '</h2>');
	echo('<p>' . $this->data['logout'] . '</p>');
}

if (isset($this->data['logouturl'])) {
	echo('<h2>' . $this->t('{status:logout}') . '</h2>');
	echo('<p>[ <a href="' . htmlspecialchars($this->data['logouturl']) . '">' . $this->t('{status:logout}') . '</a> ]</p>');
}
?>

<h2><?php echo $this->t('{core:frontpage:about_header}'); ?></h2>
<p><?php echo $this->t('{core:frontpage:about_text}'); ?></p>

<?php $this->includeAtTemplateBase('includes/footer.php'); ?>
