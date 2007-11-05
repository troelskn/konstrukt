<?php
/**
 * Used by k_wire_Container to wrap userland arguments.
 * @internal
 */
class k_wire_UserlandArguments
{
  protected $arguments;

  function __construct($arguments = array()) {
    if (!is_array($arguments)) {
      throw new k_wire_Exception("Argument for k_wire_UserlandArguments must be an array");
    }
    $this->arguments = $arguments;
  }

  function hasNext() {
    return count($this->arguments) > 0;
  }

  function next() {
    if (!$this->hasNext()) {
      throw new k_wire_Exception("Missing userland argument");
    }
    return array_shift($this->arguments);
  }
}
