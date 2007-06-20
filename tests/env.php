<?php
class TestEnv
{
	protected static $values = Array();

	static function Load(/* $filename */) {
		if (is_file(func_get_arg(0))) {
			$__get_defined_ignore = array_keys(get_defined_vars());
			include(func_get_arg(0));
			foreach (get_defined_vars() as $__get_defined_key => $__get_defined_value) {
				if (!in_array($__get_defined_key, $__get_defined_ignore)) {
					self::$values[strtolower($__get_defined_key)] = $__get_defined_value;
				}
			}
		}
	}

	static function Get($key) {
		if (!isset(self::$values[strtolower($key)])) {
			throw new Exception("Config value '$key' not set");
		}
		return self::$values[strtolower($key)];
	}

	static function Discover($rel = "") {
		$dir = self::Get('test_case_dir') . $rel;

		$d = dir($dir);
		$filenames = Array();
		while (false !== ($filename = $d->read())) {
			if (is_dir($dir.$filename) && substr($filename, 0,1) != ".") {
				foreach (self::Discover($rel.$filename."/") as $tmp) {
					$filenames[$tmp] = self::Get('test_case_dir').$tmp;
				}
			} else if (preg_match("/(.*)\\.test\\.php\$/", $filename)) {
				$filenames[$rel.$filename] = self::Get('test_case_dir').$rel.$filename;
			}
		}
		$d->close();
		return $filenames;
	}
}
?>