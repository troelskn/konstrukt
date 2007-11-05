<?php
/**
 * Thrown if preventLocatorPropagation is enabled, and k_wire_Locator is requested as a dependency.
 */
class k_wire_LocatorPropagationException extends k_wire_Exception
{
  function __construct() {
    parent::__construct("Locator can't be propagated when preventLocatorPropagation is enabled.");
  }
}
