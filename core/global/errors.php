<?php
# Copyright (C) 2008-2010 Ali Gangji
# Distributed under the terms of the GNU General Public License v3
/**
 * This file is part of StarbugPHP
 * @file core/global/errors.php
 * @author Ali Gangji <ali@neonrain.com>
 * @ingroup errors
 */
/**
 * @defgroup errors
 * global functions
 * @ingroup global
 */
/**
 * fetch any data validation errors
 * @ingroup errors
 * @param string $key the model or field to get errors for. If this is empty all errors will be returned. To get errors on a model pass the name of the model. To get errors on a field use array notation such as 'users[first_name]'
 * @return array errors indexed by model and field, empty if no errors
 */
function errors($key="", $values=false) {
	if (is_bool($key)) {
		$values = $key;
		$key = "";
	}
	global $sb;
	$parts = explode("[", $key);
	$errors = $sb->errors;
	if (!empty($key)) foreach ($parts as $p) $errors = $errors[rtrim($p, ']')];
	if ($values) return $errors;
	else return (!empty($errors));
}
/**
 * set validation error
 * @ingroup errors
 * @param $model the model name
 * @param $field the field name
 * @param $error the error message
 */
function error($error, $field="global", $model="") {
	global $sb;
	efault($model, $sb->active_scope);
	efault($sb->errors[$model], array());
	efault($sb->errors[$model][$field], array());
	$sb->errors[$model][$field][] = $error;
	if (Etc::ENABLE_ERROR_LOG) {
		raw_query("INSERT INTO ".P("errors")." (type, field, message, action, created, modified) VALUES ('".$model."', '".$field."', ".$sb->db->quote($error).", '".$_POST['action'][$model]."', NOW(), NOW())");
	}
}
/**
 * get or set the error scope
 * @ingroup errors
 * @param $value if not null, the value to set the scope to
 * @return the active scope. if a value is passed, this will return the scope that is active before setting the value
 */
function error_scope($value=null) {
	global $sb;
	$scope = $sb->active_scope;
	if ($value != null) $sb->active_scope = $value;
	return $scope;
}
/**
 * check that an action was called and no errors occurred
 * @ingroup errors
 * @param string $model the model name
 * @param string $action the function name
 * @return bool true if the function was called without returning errors
 */
function success($model, $action) {
	global $sb;
	return (($_POST['action'][$model] == $action) && (empty($sb->errors[$model])));
}

/**
 * check if an action was called and errors were produced
 * @ingroup errors
 * @param string $model the model name
 * @param string $action the function name
 * @return bool true if the function was called and produced errors
 */
function failure($model, $action) {
	global $sb;
	return (($_POST['action'][$model] == $action) && (!empty($sb->errors[$model])));
}
?>
