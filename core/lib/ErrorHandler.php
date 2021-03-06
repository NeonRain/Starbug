<?php
# Copyright (C) 2008-2010 Ali Gangji
# Distributed under the terms of the GNU General Public License v3
/**
 * This file is part of StarbugPHP
 * @file core/lib/ErrorHandler.php
 * error handler
 * @author Ali Gangji <ali@neonrain.com>
 * @ingroup core
 */
$sb->provide("core/lib/ErrorHandler");
/**
 * Error handler
 * @ingroup core
 */
class ErrorHandler {

	/**
	 * exception handler
	 */
	function handle_exception($exception) {
		restore_error_handler();
		restore_exception_handler();

		ob_end_clean();

		$error = array(
			"message" => $exception->getMessage(),
			"file" => $exception->getFile(),
			"line" => $exception->getLine()
		);

		$traces = ErrorHandler::expand_evals($error, $exception->getTrace());
		if (false !== strpos($error['file'], "eval()'d code")) {
			$error = array_shift($traces);
		}
		$error['traces'] = $traces;
		
		assign("error", $error);
		if (defined('SB_CLI')) render("exception-cli");
		else render("exception");
		exit(1);
	}
	
	/**
	 * error handler
	 */
	function handle_error($errno, $errstr, $errfile, $errline) {
		restore_error_handler();
		restore_exception_handler();
		
		ob_end_clean();
		
		$trace=debug_backtrace();
		// skip the first 3 stacks as they do not tell the error position
		if (count($trace)>2) $trace = array_slice($trace, 2);

			switch($errno) {
				case E_WARNING:
					$type = 'PHP warning: ';
					break;
				case E_NOTICE:
					$type = 'PHP notice: ';
					break;
				case E_USER_ERROR:
					$type = 'User error: ';
					break;
				case E_USER_WARNING:
					$type = 'User warning: ';
					break;
				case E_USER_NOTICE:
					$type = 'User notice: ';
					break;
				case E_RECOVERABLE_ERROR:
					$type = 'Recoverable error: ';
					break;
				case E_STRICT:
					$type = 'Strict error: ';
					break;
				default:
					$type = 'PHP error: ';
			}
			$error = array(
				'message'=>$type.$errstr,
				'file'=>$errfile,
				'line'=>$errline
			);

			$traces = ErrorHandler::expand_evals($error, $trace);
			if (false !== strpos($error['file'], "eval()'d code")) {
				if (empty($traces[0]['message'])) $traces[0]['message'] = $error['message'];
				$error = array_shift($traces);
			}
			$error['traces'] = $traces;

			if(!headers_sent()) header("HTTP/1.0 500 PHP Error");
			assign("error", $error);
			if (defined('SB_CLI')) render("exception-cli");
			else render("exception");
			exit(1);
	}
	
	/**
	 * expand eval'd code messages from a trace where possible
	 */
	function expand_evals($error, $traces) {
		$ret = array();
		foreach ($traces as $i => $trace) {
			if (false !== strpos($trace['file'], "/modules/db/classes/db.php")) {
				//interpret path from class
				$ret = array_merge($ret, ErrorHandler::expand_eval($trace, $error, "model"));
				$trace = array_pop($ret);
			} else if (isset($trace['class']) && false !== strpos($trace['class'], "__")) {
				$ret = array_merge($ret, ErrorHandler::expand_eval($trace, $error, "model"));
			} else if ($trace['function'] == 'eval' || (false != strpos($trace['file'], 'eval()\'d code'))) {
				$path = str_replace(BASE_DIR, "", $trace['file']);
				$expand = $i ? array_pop($ret) : $error;
				if ($path == "/core/lib/Renderer.php") {
					//pull from renderer eval stack
					$ret = array_merge($ret, ErrorHandler::expand_eval($expand, $trace, "renderer"));
				}
			}
			$ret[] = $trace;
		}
		return $ret;
	}
	
	function expand_eval($expand, $parent, $type="") {
		$ret = array();
		if (empty($type)) {
			if (false !== strpos($expand['file'], "core/lib/Renderer.php")) $type = "renderer";
			else if (false !== strpos($expand['file'], "modules/db/classes/db.php")) $type = "model";
		}
		if ($type == "renderer") {
			//pull from renderer eval stack
			global $renderer;
			//if the previous trace item is not in the same file we need to push a new trace and interpret the previous one
			if (isset($parent['file']) && 0 !== strpos($expand['file'], $parent['file'])) {
				$line = reset(explode(" ", end(explode("eval()'d code on line ", $expand['message']))));
				$ret[] = array("file" => array_pop($renderer->stack), "line" => $line);
				$ret = array_merge(ErrorHandler::expand_eval($expand, array()), $ret);
			} else {
				$expand['file'] = array_pop($renderer->stack);
				$ret[] = $expand;
			}
		} else if ($type == "model") {
			//interpret path from class
			if ($expand['class'] == "sb") {
				if (false != strpos($parent['message'], ", called in ")) {
					$message = explode(", called in ", $parent['message']);
					$parent['message'] = $message[0];
					$class = end(explode(" ", reset(explode("::", $message[0]))));
					$parts = explode("__", $class);
					$parent['file'] = BASE_DIR."/".str_replace("_", "/", $parts[0])."/models/".$parts[1].".php";
					if (false !== strpos($message[1], "modules/db/classes/db")) $parent['line'] = reset(explode(" ", end(explode(" code on line ", $message[1]))));
				}
				$ret[] = $parent;
			} else {
				$parts = explode("__", $expand['class']);
				$expand['file'] = BASE_DIR."/".str_replace("_", "/", $parts[0])."/models/".$parts[1].".php";
				$expand['message'] = $parent['message'];
				$expand['line'] = $parent['line'];
				$ret[] = $expand;
			}
		}
		return $ret;
	}
	
	/**
	 * renders source around an line. used for exception and error output details
	 */
	function render_source($file, $line, $max) {
		$line--;	// adjust line number to 0-based from 1-based
		if($line<0 || ($lines=@file($file))===false || ($count=count($lines))<=$line) return '';

		$half = (int)($max/2);
		$start = ($line-$half>0) ? $line-$half : 0;
		$end = ($line+$half<$count) ? $line+$half : $count-1;
		$lineNumberWidth = strlen($end+1);

		$output='';
		for($i=$start;$i<=$end;++$i)
		{
			$isline = $i===$line;
			$code=sprintf("<span class=\"ln".($isline?' error-ln':'')."\">%0{$lineNumberWidth}d</span> %s",$i+1,htmlentities(str_replace("\t",'    ',$lines[$i])));
			if(!$isline)
				$output.=$code;
			else
				$output.='<span class="error">'.$code.'</span>';
		}
		return '<div class="code"><pre>'.$output.'</pre></div>';
	}
	
	/**
	 * converts function arguments from a trace into a readable string
	 */
	function argumentsToString($args) {
		$count=0;

		$isAssoc=$args!==array_values($args);

		foreach($args as $key => $value) {
			$count++;
			if($count>=5) {
				if($count>5) unset($args[$key]);
				else $args[$key]='...';
				continue;
			}

			if(is_object($value)) $args[$key] = get_class($value);
			else if(is_bool($value)) $args[$key] = $value ? 'true' : 'false';
			else if(is_string($value)) {
				if(strlen($value)>64) $args[$key] = '"'.substr($value,0,64).'..."';
				else $args[$key] = '"'.$value.'"';
			}
			else if(is_array($value)) $args[$key] = 'array('.ErrorHandler::argumentsToString($value).')';
			else if($value===null) $args[$key] = 'null';
			else if(is_resource($value)) $args[$key] = 'resource';

			if(is_string($key)) {
				$args[$key] = '"'.$key.'" => '.$args[$key];
			} else if($isAssoc) {
				$args[$key] = $key.' => '.$args[$key];
			}
		}
		$out = implode(", ", $args);

		return $out;
	}
}
