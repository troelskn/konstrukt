<?php
/**
 * A dependency on a userland supplied value.
 * This will pop an argument from the userland arguments.
 * Since userland arguments are only passed to transiently created instance, adding such a
 * dependency will limit the class to be created transiently.
 */
class wire_RequiredUserDependency implements wire_iDependencySource
{
  function resolve(wire_iLocator $locator, wire_UserlandArguments $args) {
    return $args->next();
  }
}
