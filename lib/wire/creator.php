<?php
/**
 * A proxy to limit a wire_iLocator to the capabilities of a wire_iCreator.
 * This is useful in combination with the preventLocatorPropagation setting on wire_Container
 */
class wire_Creator implements wire_iCreator
{
  protected $creator;

  function __construct(wire_iCreator $creator) {
    $this->creator = $creator;
  }

  function create($classname) {
    return $this->creator->create($classname);
  }

  function createArgs($classname, $args) {
    return $this->creator->createArgs($classname, $args);
  }
}
