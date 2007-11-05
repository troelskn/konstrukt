<?php
/**
 * Default factory.
 * Assumes that constructor has no dependencies, and passes it all userland arguments.
 */
class k_wire_DefaultFactory implements k_wire_iClassFactory
{
  /**
   * Creates a new instance.
   */
  function createInstanceArgs($classname, k_wire_UserlandArguments $args) {
    $clone = clone $args;
    $args_array = array();
    while ($clone->hasNext()) {
      $args_array[] = $clone->next();
    }
    $klass = new ReflectionClass($classname);
    $ctor = $klass->getConstructor();
    if ($ctor) {
      return call_user_func_array(array($klass, 'newInstance'), $args_array);
    } else {
      return $klass->newInstance();
    }
  }

  function loadDependencies($instance, k_wire_UserlandArguments $args) {
    return $instance;
  }

}
