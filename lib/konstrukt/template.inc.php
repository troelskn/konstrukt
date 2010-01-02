<?php
/**
 * This provides a very simple template engine - Essentially, it's just
 * a wrapper around include, using output buffering to grab and return the output.
 */
class k_Template {
  /** @var string */
  protected $path;
  /**
    * @param string
    * @return void
    */
  function __construct($path) {
    $this->path = $path;
  }
  /**
    * @param k_Context
    * @return string
    */
  function render($context /*, $model = array() */) {
    $__previous_context = isset($GLOBALS['k_current_context']) ? $GLOBALS['k_current_context'] : null;
    $GLOBALS['k_current_context'] = $context;
    if (func_num_args() > 1) {
      extract(func_get_arg(1));
    }
    ob_start();
    try {
      if (!k_search_include_path($this->path)) {
        throw new Exception("Unable to find file '" . $this->path . "'");
      }
      include($this->path);
      $buffer = ob_get_clean();
      $GLOBALS['k_current_context'] = $__previous_context;
      return $buffer;
    } catch (Exception $ex) {
      ob_end_clean();
      $GLOBALS['k_current_context'] = $__previous_context;
      throw $ex;
    }
  }
}

/**
 * Transient global variable. Only available while view is rendering.
 * You should only ever refer this variable from within view-helper functions.
 */
$GLOBALS['k_current_context'] = null;

/**
 * Interface for getting a template.
 * You can provide a factory to return wrappers around other template engines, such as smarty.
 */
interface k_TemplateFactory {
  function create($file_name);
}

class k_DefaultTemplateFactory implements k_TemplateFactory {
  protected $template_dir;
  function __construct($template_dir) {
    $this->template_dir = $template_dir;
  }
  function create($file_name) {
    return new k_Template(
      $this->template_dir . DIRECTORY_SEPARATOR . $file_name . '.tpl.php');
  }
}

/**
 * Global view-helpers.
 * These have a high risk of clashing with other frameworks.
 */

/**
 * Escapes a string for embedding in html.
 */
function escape($str) {
  return htmlspecialchars($str, ENT_QUOTES);
}

/**
 * Escapes and prints a string.
 */
function e($str) {
  echo htmlspecialchars($str, ENT_QUOTES);
}

/**
 * Translates a string.
 */
function t($str) {
  return $GLOBALS['k_current_context']->translator()->translate($str);
}

/**
 * Generates a url from the current context.
 */
function url($path = "", $params = array()) {
  return $GLOBALS['k_current_context']->url($path, $params);
}
