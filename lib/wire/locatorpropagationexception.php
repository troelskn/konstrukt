<?php
/**
 * Thrown if preventLocatorPropagation is enabled, and wire_Locator is requested as a dependency.
 */
class wire_LocatorPropagationException extends wire_Exception
{
  function __construct() {
    parent::__construct("Locator can't be propagated when preventLocatorPropagation is enabled.");
  }
}
