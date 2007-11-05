<?php
/**
 * A constant value to satisfy a dependency.
 * Will neither touch userland arguments, nor the locator.
 */
class k_wire_ConstantDependency implements k_wire_iDependencySource
{
  protected $value;

  /**
   * @param  $value  mixed  Can be anything.
   */
  function __construct($value) {
    $this->value = $value;
  }

  function resolve(k_wire_iLocator $locator, k_wire_UserlandArguments $args) {
    return $this->value;
  }
}
