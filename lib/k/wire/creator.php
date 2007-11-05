<?php
/**
 * A proxy to limit a k_wire_iLocator to the capabilities of a k_wire_iCreator.
 * This is useful in combination with the preventLocatorPropagation setting on k_wire_Container
 */
class k_wire_Creator implements k_wire_iCreator
{
  protected $creator;

  function __construct(k_wire_iCreator $creator) {
    $this->creator = $creator;
  }

  function create($classname) {
    return $this->creator->create($classname);
  }

  function createArgs($classname, $args) {
    return $this->creator->createArgs($classname, $args);
  }
}
