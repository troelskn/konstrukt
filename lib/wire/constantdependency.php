<?php
/**
 * A constant value to satisfy a dependency.
 * Will neither touch userland arguments, nor the locator.
 */
class wire_ConstantDependency implements wire_iDependencySource
{
  protected $value;

  /**
   * @param  $value  mixed  Can be anything.
   */
  function __construct($value) {
    $this->value = $value;
  }

  function resolve(wire_iLocator $locator, wire_UserlandArguments $args) {
    return $this->value;
  }
}
