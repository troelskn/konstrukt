<?php
/**
  * Adapter for managed static function.
  *
  * This class is a static callback registry for deferring global static functions to callbacks.
  * There are no sanitychecks, in order to maximise performance. You're expected to know, what you're doing.
  *
  * This makes it possible to redefine behaviour of certain static functions, at runtime.
  * The functions currently supported are:
  *
  *   e()       Prints string, formatted with output filters.
  *   __()      Translate phrase.
  *   trace()   Dumps one or more variables for debugging.
  *   url()     Build a URL.
  *
  * You can register a callback to handle each of these functions with k_StaticAdapter.
  * The following code will defer ``e()`` to ``$callback``:
  * ::
  * k_StaticAdapter::connect('e', $callback);
  *
  * To remove the callback, and restore the previous handler, use:
  * ::
  * k_StaticAdapter::disconnect('e');
  */
class k_StaticAdapter
{
  protected static $callbacks = Array(
    'e' => Array(Array('k_StaticAdapter', 'defaultOutput')),
    '__' => Array(Array('k_StaticAdapter', 'defaultTranslate')),
    'trace' => Array(Array('k_StaticAdapter', 'defaultTrace')),
    'url' => Array(Array('k_StaticAdapter', 'defaultBuildUrl')),
  );

  /**
    * Invokes a call to one of the callbacks.
    * This is not supposed to be called directly, but is used by the static function stubs.
    * @var  string    $function  Name of function.
    * @var  array     $args      Array of arguments to the callback.
    */
  public static function call($function, $args) {
    return call_user_func_array(self::$callbacks[$function][count(self::$callbacks[$function]) - 1], $args);
  }

  /**
    * Connects a callback to one of the managed functions.
    * @var  string    $function  Name of function.
    * @var  callback  $handler   A valid callback.
    */
  public static function connect($function, $handler) {
    self::$callbacks[$function][] = $handler;
  }

  /**
    * Restores the previous handler for a managed function.
    * @var  string    $function  Name of function.
    */
  public static function disconnect($function) {
    array_pop(self::$callbacks[$function]);
  }

  /**
    * Default implementation for e()
    * Echoes input to stdout.
    */
  protected static function defaultOutput($str) {
    echo $str;
  }

  /**
    * Default implementation for __()
    * Returns input.
    */
  protected static function defaultTranslate($str) {
    return $str;
  }

  /**
    * Default implementation for trace()
    * Dumps input variables.
    */
  protected static function defaultTrace() {
    if (php_sapi_name() != 'cli') {
      echo "<pre>";
    }
    foreach (func_get_args() as $arg) {
        var_dump($arg);
        echo "\n";
    }
    if (php_sapi_name() != 'cli') {
      echo "</pre>";
    }
  }

  /**
    * Default implementation for url()
    * This is a stub. No default implementation exists.
    */
  protected static function defaultBuildUrl() {
    throw new Exception("No default implementation for managed static function url()");
  }
}

/**
  * Prints string, formatted with output filters.
  * This function is managed by k_StaticAdapter.
  * @see k_StaticAdapter::defaultOutput()
  */
function e() {
  $args = func_get_args();
  return k_StaticAdapter::call('e', $args);
}

/**
  * Translate phrase.
  * This function is managed by k_StaticAdapter.
  * @see k_StaticAdapter::defaultTranslate()
  */
function __() {
  $args = func_get_args();
  return k_StaticAdapter::call('__', $args);
}

/**
  * Build a URL.
  * This function is managed by k_StaticAdapter.
  * @see k_StaticAdapter::defaultBuildUrl()
  */
function url() {
  $args = func_get_args();
  return k_StaticAdapter::call('url', $args);
}

/**
  * Dumps one or more variables for debugging.
  * This function is managed by k_StaticAdapter.
  * @see k_StaticAdapter::defaultTrace()
  */
function trace() {
  $args = func_get_args();
  return k_StaticAdapter::call('trace', $args);
}