<select<? foreach ($attributes as $key => $val) if (!empty($val) && !is_array($val) && !in_array($key, array("label", "field"))) echo ' '.$key.'="'.$val.'"'; ?>>
	<? foreach ($options as $caption => $val) { ?>
		<option value="<?= htmlentities($val); ?>"<? if ($val == $value) echo " selected=\"true\""; ?>><?= $caption; ?></option>
	<? } ?>
</select>