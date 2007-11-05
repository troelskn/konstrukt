<?php
/**
 * A factory, which employs a callback for creating instances.
 * This is extremely flexible, but also a bit impractical. It's mostly provided
 * for backwards compatibility, and to serve very odd cases.
 * @deprecated
 */
class k_wire_CallbackFactory implements k_wire_iClassFactory
{
  protected $locator;
  protected $callback;

  /**
   * @param  $locator  k_wire_iLocator  A locator, from which to retrieve dependencies.
   * @param  $callback callback
   */
  function __construct(k_wire_iLocator $locator, $callback) {
    if (!is_callable($callback)) {
      throw new Exception("Type mismatch. Second argument should be a valid callback.");
    }
    $this->locator = $locator;
    $this->callback = $callback;
  }

  /**
   * Creates a new instance.
   */
  function createInstanceArgs($classname, k_wire_UserlandArguments $args) {
    $clone = clone $args;
    $args_array = array();
    while ($clone->hasNext()) {
      $args_array[] = $clone->next();
    }
    return call_user_func_array($this->callback, array(strtolower($classname), $args_array, $this->locator));
  }

  function loadDependencies($instance, k_wire_UserlandArguments $args) {
    return $instance;
  }

}
