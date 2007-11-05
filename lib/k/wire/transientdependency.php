<?php
/**
 * A dependency on a transient instance.
 * There can be an arbitrary number of transient instances per container.
 * May consume userland arguments. These are cascaded to the locator, so sub-dependencies may also
 * use of the userland arguments.
 */
class k_wire_TransientDependency implements k_wire_iDependencySource
{
  protected $classname;

  /**
   * @param  $classname  string  Name of the class for which to create a transient (new) instance.
   */
  function __construct($classname) {
    $this->classname = $classname;
  }

  function resolve(k_wire_iLocator $locator, k_wire_UserlandArguments $args) {
    return $locator->createArgs($this->classname, $args);
  }
}
