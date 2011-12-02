<?php
# Copyright (C) 2008-2010 Ali Gangji
# Distributed under the terms of the GNU General Public License v3
/**
 * This file is part of StarbugPHP
 * @file core/global/templates.php
 * @author Ali Gangji <ali@neonrain.com>
 * @ingroup templates
 */
/**
 * @defgroup templates
 * global functions
 * @ingroup global
 */
/**
	* assign vars to the global renderer
	* @ingroup templates
	* @param string $key the variable name
	* @param string $value the value to assign
	*/
function assign($key, $value) {
	global $sb;
	$sb->import("core/lib/Renderer");
	global $renderer;
	$renderer->assign($key, $value);
}
/**
	* render a template
	* @ingroup templates
	* @param string $path the path, relative to the request prefix and without the file extension
	* @param string $scope a rendering scope. if this is empty we will use the active scope. If there is no active scope we will use 'global'
	*/
function render($path, $scope="global") {
	global $sb;
	$sb->import("core/lib/Renderer");
	global $renderer;
	$renderer->render($path, $scope);
}
/**
	* capture a rendered template
	* @ingroup templates
	* @param string $path the path, relative to the request prefix and without the file extension
	*/
function capture($path, $scope="global") {
	global $sb;
	$sb->import("core/lib/Renderer");
	global $renderer;
	return $renderer->capture($path, $scope);
}
/**
	* render a region
	* @ingroup templates
	* @param string $region the region to render
	*/
function render_region($region) {
	if (is_array($region)) foreach($region as $key => $value) render_region($key);
	else {
		assign("region", $region);
		render("region");
	}
}
/**
	* render a view
	* @ingroup templates
	* @param string $view the view to render
	*/
function render_view($view="") {
	render($view, "views");
}
/**
	* render a form
	* @ingroup templates
	* @param string $form the form to render
	*/
function render_form($form="") {
	render($form, "forms");
}
?>
