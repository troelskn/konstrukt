<?php
/**
 * This provides a very simple template engine - Essentially, it's just
 * a wrapper around include, using output buffering to grab and return the output.
 * There are hooks for plugging more complex helper scripts in (so-called view helpers)
 */
class k_Template {
  /** @var string */
  protected $path;
  /** @var k_Context */
  protected $context;
  /** @var [] View Helper */
  protected $helpers = array();
  /**
    * @param string
    * @param [] View Helper
    * @return void
    */
  function __construct($path, $helpers = array()) {
    $this->path = $path;
    $this->helpers = $helpers;
  }
  function __call($name, $arguments) {
    if (!isset($this->helpers[strtolower($name)])) {
      throw new Exception("Call to undefined view-helper '$name'");
    }
    array_unshift($arguments, $this, $this->context);
    return call_user_func_array($this->helpers[strtolower($name)], $arguments);
  }
  function escape($str) {
    return htmlspecialchars($str, ENT_QUOTES);
  }
  function e($str) {
    echo $this->escape($str);
  }
  function t($str) {
    return $this->context->t($str);
  }
  function __($str) {
    return $this->t($str);
  }
  function url($path = "", $params = array()) {
    return $this->context->url($path, $params);
  }
  /**
    * @param k_Context
    * @return string
    */
  function render($context /*, $model = array() */) {
    self::InstallGlobals();
    if (func_num_args() > 1) {
      extract(func_get_arg(1));
    }
    $__old_handler_e__ = $GLOBALS['_global_function_callback_e'];
    $__old_handler_____ = $GLOBALS['_global_function_callback___'];
    $__old_handler_t__ = $GLOBALS['_global_function_callback_t'];
    $__old_handler_url__ = $GLOBALS['_global_function_callback_url'];
    $GLOBALS['_global_function_callback_e'] = array($this, 'e');
    $GLOBALS['_global_function_callback___'] = array($this, '__');
    $GLOBALS['_global_function_callback_t'] = array($this, '__');
    $GLOBALS['_global_function_callback_url'] = array($this, 'url');
    $this->context = $context;
    ob_start();
    try {
      if (!k_search_include_path($this->path)) {
        throw new Exception("Unable to find file '" . $this->path . "'");
      }
      include($this->path);
      $buffer = ob_get_clean();
      $GLOBALS['_global_function_callback_e'] = $__old_handler_e__;
      $GLOBALS['_global_function_callback___'] = $__old_handler_____;
      $GLOBALS['_global_function_callback_t'] = $__old_handler_t__;
      $GLOBALS['_global_function_callback_url'] = $__old_handler_url__;
      $this->context = null;
      return $buffer;
    } catch (Exception $ex) {
      ob_end_clean();
      $GLOBALS['_global_function_callback_e'] = $__old_handler_e__;
      $GLOBALS['_global_function_callback___'] = $__old_handler_____;
      $GLOBALS['_global_function_callback_t'] = $__old_handler_t__;
      $GLOBALS['_global_function_callback_url'] = $__old_handler_url__;
      $this->context = null;
      throw $ex;
    }
  }
  /**
   * Installs global functions
   */
  protected static function InstallGlobals() {
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
        $GLOBALS['_global_function_callback_e'] = null;
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
        $GLOBALS['_global_function_callback___'] = null;
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
        $GLOBALS['_global_function_callback_t'] = null;
      }
    }

    if (!function_exists('url')) {
      /**
       * This function is dynamically redefinable.
       * @see $GLOBALS['_global_function_callback_url']
       */
      function url($args = null) {
        $args = func_get_args();
        return call_user_func_array($GLOBALS['_global_function_callback_url'], $args);
      }
      if (!isset($GLOBALS['_global_function_callback_url'])) {
        $GLOBALS['_global_function_callback_url'] = null;
      }
    }
  }
}

interface k_TemplateFactory {
  function loadViewHelper($helper);
  function create($file_name);
}

class k_DefaultTemplateFactory implements k_TemplateFactory {
  protected $template_dir;
  protected $helpers = array();
  function __construct($template_dir) {
    $this->template_dir = $template_dir;
  }
  function loadViewHelper($helper) {
    foreach (get_class_methods($helper) as $method) {
      $this->helpers[strtolower($method)] = array($helper, $method);
    }
  }
  function create($file_name) {
    return new k_Template(
      $this->template_dir . DIRECTORY_SEPARATOR . $file_name . '.tpl.php',
      $this->helpers);
  }
}
