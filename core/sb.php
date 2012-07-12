<?php
# Copyright (C) 2008-2010 Ali Gangji
# Distributed under the terms of the GNU General Public License v3
/**
 * This file is part of StarbugPHP
 * @file core/sb.php
 * The global sb object. provides data, errors, import/provide, load and pub/sub. The backbone of Starbug
 * @author Ali Gangji <ali@neonrain.com>
 * @ingroup core
 */
/**
 * The sb class. Provides data, errors, import/provide, pub/sub, and load to the rest of the application. The core component of StarbugPHP.
 * @ingroup core
 */
class sb {
	/**#@+
	* @access public
	*/
	/**
	 * @var db The db class is a PDO wrapper
	 */
	var $db;
	/**
	 * @var string holds the active scope (usually 'global' or a model name)
	 */
	var $active_scope = "global";
	/**
	 * @var array holds the utils that have been provided
	 */
	var $provided = array();
	/**
	 * @var array holds validation errors
	 */
	var $errors = array();
	/**
	 * @var array holds alerts
	 */
	var $alerts = array();
	/**#@-*/
	/**
	 * @var array holds mixed in objects
	 */
	var $imported = array();
	/**
	 * @var array holds function names of mixed in objects
	 */
	var $imported_functions = array();


	/**
	 * constructor. connects to db and sets default $_SESSION values
	 */
	function __construct() {
		if (php_sapi_name() != Etc::CLI_SAPI_NAME) {
			set_exception_handler(array($this,'handle_exception'));
			set_error_handler(array($this,'handle_error'), error_reporting());
			register_shutdown_function(array($this, 'handle_shutdown')); 
		}
		$this->db = new db('mysql:host='.Etc::DB_HOST.';dbname='.Etc::DB_NAME, Etc::DB_USERNAME, Etc::DB_PASSWORD, Etc::PREFIX);
		$this->db->set_debug(true);
	}

	/**
	 * since you can't subscribe the handle 'include', use sb::load
	 * @param string $what 'jsforms' works for either 'jsforms.php' or 'jsforms/jsforms.php'
	 */
	function load($what) {
		global $request;
		if (file_exists($what.".php")) include($what.".php");
		else {
			$token = split("/", $what); $token = $what."/".end($token).".php";
			if (file_exists($token)) include($token);
		}
	}

	/**
	 * publish a topic to any subscribers or hooks
	 * @param string $topic the topic name you would like to publish
	 * @param mixed $args any additional parameters will be passed in an array to the subscriber
	 */
	function publish($topic, $args=null) {
		global $request; global $sb;
		$return = array();
		$args = func_get_args();
		if (false !== strpos($topic, ".")) {
			list($tags, $topic) = explode(".", $topic);
			$tags = array(array("slug" => $tags));
		} else $tags = (isset($request->tags)) ? $request->tags : array(array("slug" => "global"));
		foreach ($tags as $tag) {
			foreach (locate("$tag[slug].$topic.php", "hooks") as $hook) if (file_exists($hook)) include($hook);
			$subscriptions = (file_exists(BASE_DIR."/etc/hooks/$tag[slug].$topic.json")) ? json_decode(file_get_contents(BASE_DIR."/etc/hooks/$tag[slug].$topic"), true) : array();
			foreach ($subscriptions as $priority) {
				foreach($priority as $handle) {
					if (false !== strpos($handle['handle'], "::")) $handle['handle'] = explode("::", $handle['handle']);
					$return[] = call_user_func($handle['handle'], $handle['args'], $args);
				}
			}
		}
		return $return;
	}

	/**
	 * import function. only imports once when used with provide
	 * @param string $loc path of file to import without '.php' at the end
	 */
	function import($loc) {global $sb; $args = func_get_args(); foreach($args as $l) if (!isset($this->provided[$l])) include(BASE_DIR."/".$l.".php");}

	/**
	 * when imported use provide to prevent further imports from attempting to include it again
	 * @param string $loc the imported location. if i were to use $sb->import("util/form"), util/form.php would have $sb->provide("util/form") at the top
	 */
	function provide($loc) {$this->provided[$loc] = true;}

	/**
	 * get a model by name
	 * @param string $name the name of the model, such as 'users'
	 * @return the instantiated model
	 */
	function get($name) {
		return $this->db->model($name);
	}

	/**
	 * run a model action if permitted
	 * @param string $key the model name
	 * @param string $value the function name
	 */
	function post_act($key, $value) {
		if ($object = $this->db->model($key)) {
			$this->active_scope = $key;
			$permits = isset($_POST[$key]['id']) ? $this->db->query($key, "action:$value  where:$key.id='".$_POST[$key]['id']."'") : $this->db->query($key, "action:$value  priv_type:table");
			if ($permits || ($_SESSION[P('memberships')] & 1)) $object->$value($_POST[$key]);
			else request()->forbidden();
			$this->active_scope = "global";
			if (!empty($this->errors[$key])) request()->return_path();
		}
	}

	/**
	 * check $_POST['action'] for posted actions and run them through post_act
	 */
	function check_post() {if (!empty($_POST['action'])) foreach($_POST['action'] as $key => $val) $this->post_act($key, $val);}
	
	/**
	 * exception handler
	 */
	public function handle_exception($exception) {
		$this->import("core/lib/ErrorHandler");
		ErrorHandler::handle_exception($exception);
	}
	
	function handle_error($errno, $errstr, $errfile, $errline) {
		$this->import("core/lib/ErrorHandler");
		ErrorHandler::handle_error($errno, $errstr, $errfile, $errline);
	}
	
	function handle_shutdown() {
		if(is_null($e = error_get_last()) === false) {
			ob_end_flush();
		}
	}
	
	/**
	 * mixin an object to import its functions into this object
	 * @param object $object the object to mixin
	 */
	protected function mixin($object) {
		$new_import = new $object();
		$import_name = get_class($new_import);
		$import_functions = get_class_methods($new_import);
		array_push($this->imported, array($import_name, $new_import));
		foreach($import_functions as $key => $function_name) $this->imported_functions[$function_name] = &$new_import;
	}

	/**
	 * Handler for calls to non-existant functions. Allows calling of mixed in functions.
	 */
	public function __call($method, $args) {
		if(array_key_exists($method, $this->imported_functions)) {
			$args[] = $this;  
			return call_user_func_array(array($this->imported_functions[$method], $method), $args);
		}
		throw new Exception ('Call to undefined method/class function: ' . $method);
	}
}
?>
