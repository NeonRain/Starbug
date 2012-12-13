<?php open_form("model:$model  action:$action".(empty($url) ? "" : "  url:$url"), $form_attributes); ?>
	<?php
		efault($cancel_url, $url);
	?>
	<?php foreach ($fields as $name => $field) { ?>
		<?php
			if (is_string($field['display'])) {
				$values = explode(",", $field['display']);
				if (in_array($action, $values)) $field['display'] = true; 
			}	
		?>
		<?php if ($field['display'] === true) { ?>
				<?php
					if ($field['input_type'] == "password") $field['class'] .= ((empty($field['class'])) ? "" : " ")."text";
					else if ($field['input_type'] == "select") {
						if (!empty($field['filters']['alias'])) {
							$ref = explode(" ", $field['filters']['references']);
							$field['caption'] = $field['filters']['alias'];
							$field['value'] = end($ref);
							$field['from'] = reset($ref);
						}
					} else if ($field['type'] == "bool") $field['value'] = 1;
					$star = "";
					foreach ($field as $k => $v) $star .= "  $k:$v";
					if (isset($this->vars[$name."_options"])) $star .= "  ".$this->vars[$name."_options"];
				?>
				<?php f($field['input_type'], $name.$star); ?>
				<?php
					if (!empty($field['filters']['confirm'])) {
						f($field['input_type'], $field['filters']['confirm'].$star);
					}
				?>
		<?php } ?>
	<?php } ?>
	<div class="field"><button class="left positive" type="submit">Save</button><button type="button" class="negative cancel button"<?php if (!empty($cancel_url)) { ?> onclick="window.location='<?= $cancel_url; ?>'"<?php } ?>>Cancel</button></div>
	<br class="clear"/>
<?php close_form(); ?>
