<?php
/**
 * @author Niels A.D.
 * @package Ganon
 * @link http://code.google.com/p/ganon/
 * @license http://dev.perl.org/licenses/artistic.html Artistic License
 */

function str_get_dom($str, $return_root = true) {
	$a = new HTML_Parser_HTML5($str);
	return (($return_root) ? $a->root : $a);
}
function file_get_dom($file, $return_root = true, $use_include_path = false, $context = null) {
	if (version_compare(PHP_VERSION, '5.0.0', '>='))
		$f = file_get_contents($file, $use_include_path, $context);
	else {
		if ($context !== null)
			trigger_error('Context parameter not supported in this PHP version');
		$f = file_get_contents($file, $use_include_path);
	}
	return (($f === false) ? false : str_get_dom($f, $return_root));
}
function dom_format(&$root, $options = array()) {
	$formatter = new HTML_Formatter($options);
	return $formatter->format($root);
}
if (version_compare(PHP_VERSION, '5.0.0', '<')) {
	function str_split($string) {
		$res = array();
		$size = strlen($string);
		for ($i = 0; $i < $size; $i++) {
			$res[] = $string[$i];
		}
		return $res;
	}
}
if (version_compare(PHP_VERSION, '5.2.0', '<')) {
	function array_fill_keys($keys, $value) {
		$res = array();
		foreach($keys as $k) {
			$res[$k] = $value;
		}
		return $res;
	}
}

require(dirname(__FILE__) . '/gan_tokenizer.php');
require(dirname(__FILE__) . '/gan_parser_html.php');
require(dirname(__FILE__) . '/gan_node_html.php');
require(dirname(__FILE__) . '/gan_selector_html.php');
require(dirname(__FILE__) . '/gan_formatter.php');

?>