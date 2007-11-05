<?php
/**
 * A dependency on a shared instance.
 * The dependency is unique for the container.
 * Will never touch the userland arguments.
 */
class k_wire_SharedDependency implements k_wire_iDependencySource
{
  protected $classname;

  /**
   * @param  $classname  string  Name of the class for which to get a shared instance.
   */
  function __construct($classname) {
    $this->classname = $classname;
  }

  function resolve(k_wire_iLocator $locator, k_wire_UserlandArguments $args) {
    return $locator->get($this->classname);
  }
}
