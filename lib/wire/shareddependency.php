<?php
/**
 * A dependency on a shared instance.
 * The dependency is unique for the container.
 * Will never touch the userland arguments.
 */
class wire_SharedDependency implements wire_iDependencySource
{
  protected $classname;

  /**
   * @param  $classname  string  Name of the class for which to get a shared instance.
   */
  function __construct($classname) {
    $this->classname = $classname;
  }

  function resolve(wire_iLocator $locator, wire_UserlandArguments $args) {
    return $locator->get($this->classname);
  }
}
