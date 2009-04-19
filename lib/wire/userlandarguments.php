<?php
/**
 * Used by wire_Container to wrap userland arguments.
 * @internal
 */
class wire_UserlandArguments
{
  protected $arguments;

  function __construct($arguments = array()) {
    if (!is_array($arguments)) {
      throw new wire_Exception("Argument for wire_UserlandArguments must be an array");
    }
    $this->arguments = $arguments;
  }

  function hasNext() {
    return count($this->arguments) > 0;
  }

  function next() {
    if (!$this->hasNext()) {
      throw new wire_Exception("Missing userland argument");
    }
    return array_shift($this->arguments);
  }
}
