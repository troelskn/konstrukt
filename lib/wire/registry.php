<?php
/**
 * Used by wire_Container to hold shared instances.
 * @internal
 */
class wire_Registry implements wire_iLocator
{
  protected $_instances = array();

  function get($classname) {
    $classname = strtolower($classname);
    return isset($this->_instances[$classname]) ? $this->_instances[$classname] : NULL;
  }

  /**
   * Returns true, if the class has already been registered.
   * @param  $classname  string  Name of the class.
   * @return boolean
   */
  function has($classname) {
    return isset($this->_instances[strtolower($classname)]);
  }

  /**
   * Stores an instance in the registry.
   * @param  $classname  string  Name of the class to store.
   * @param  $instance   object  Instance to store.
   */
  function set($classname, $instance) {
    $this->_instances[strtolower($classname)] = $instance;
  }

  function __get($classname) {
    return $this->get($classname);
  }

  function __isset($classname) {
    return $this->has($classname);
  }

}
