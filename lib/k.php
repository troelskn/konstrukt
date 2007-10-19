<?php
// The classloader can't be autoloaded, and so must be manually included.
require_once 'k/classloader.php';

// Here we hook up the default autoloader. With this, you don't need to explicitly include
// files, as long as they follow the Konstrukt naming scheme.
// Note that Konstrukt differs slightly from PEAR, in that all filenames are lowercase.
// This is to ensure portability across filesystems, with case sensitive filenames (*nix)
spl_autoload_register(Array('k_ClassLoader', 'autoload'));

// Dynamic global functions
if (!function_exists('e')) {
  /**
   * This function is dynamically redefinable.
   * @see $GLOBALS['_global_function_callback_e']
   */
  function e($args) {
    $args = func_get_args();
    return call_user_func_array($GLOBALS['_global_function_callback_e'], $args);
  }
  if (!isset($GLOBALS['_global_function_callback_e'])) {
    $GLOBALS['_global_function_callback_e'] = NULL;
  }
}

if (!function_exists('__')) {
  /**
   * This function is dynamically redefinable.
   * @see $GLOBALS['_global_function_callback___']
   */
  function __($args) {
    $args = func_get_args();
    return call_user_func_array($GLOBALS['_global_function_callback___'], $args);
  }
  if (!isset($GLOBALS['_global_function_callback___'])) {
    $GLOBALS['_global_function_callback___'] = NULL;
  }
}

if (!function_exists('t')) {
  /**
   * This function is dynamically redefinable.
   * @see $GLOBALS['_global_function_callback_t']
   */
  function t($args) {
    $args = func_get_args();
    return call_user_func_array($GLOBALS['_global_function_callback_t'], $args);
  }
  if (!isset($GLOBALS['_global_function_callback_t'])) {
    $GLOBALS['_global_function_callback_t'] = NULL;
  }
}

if (!function_exists('url')) {
  /**
   * This function is dynamically redefinable.
   * @see $GLOBALS['_global_function_callback_url']
   */
  function url($args) {
    $args = func_get_args();
    return call_user_func_array($GLOBALS['_global_function_callback_url'], $args);
  }
  if (!isset($GLOBALS['_global_function_callback_url'])) {
    $GLOBALS['_global_function_callback_url'] = NULL;
  }
}
