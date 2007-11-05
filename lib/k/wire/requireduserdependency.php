<?php
/**
 * A dependency on a userland supplied value.
 * This will pop an argument from the userland arguments.
 * Since userland arguments are only passed to transiently created instance, adding such a
 * dependency will limit the class to be created transiently.
 */
class k_wire_RequiredUserDependency implements k_wire_iDependencySource
{
  function resolve(k_wire_iLocator $locator, k_wire_UserlandArguments $args) {
    return $args->next();
  }
}
