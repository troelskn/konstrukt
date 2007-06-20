<?php
/**
  * Provides functionality for including files.
  */
class k_ClassLoader
{
  /**
    * Default autoloader for Konstrukt naming scheme.
    */
  static function autoload($classname) {
    $filename = str_replace('_', '/', strtolower($classname)).'.php';
    if (self::SearchIncludePath($filename)) {
      require_once($filename);
    }
  }

  /**
    * Searches the include-path for a filename.
    * Returns the absolute path (realpath) if found or FALSE
    * @return mixed
    */
  static function SearchIncludePath($filename) {
    if (is_file($filename)) {
      return $filename;
    }
    foreach (explode(PATH_SEPARATOR, ini_get("include_path")) as $path) {
      if (strlen($path) > 0 && $path{strlen($path)-1} != DIRECTORY_SEPARATOR) {
        $path .= DIRECTORY_SEPARATOR;
      }
      $f = realpath($path . $filename);
      if ($f && is_file($f)) {
        return $f;
      }
    }
    return FALSE;
  }
}