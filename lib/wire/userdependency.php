<?php
/**
 * A userland suppliable value, which can be left out, in which case a default is applied.
 * This will only pop a userland argument, if any are left. Otherwise, a default (constant)
 * value is supplied. In behaviour, it's a mix of wire_RequiredUserDependency and wire_ConstantDependency
 */
class wire_UserDependency extends wire_RequiredUserDependency
{
  protected $defaultValue;

  /**
   * @param  $value  mixed  The default avalue to fall back on, if no argument is supplied.
   */
  function __construct($defaultValue = NULL) {
    $this->defaultValue = $defaultValue;
  }

  function resolve(wire_iLocator $locator, wire_UserlandArguments $args) {
    if (!$args->hasNext()) {
      return $this->defaultValue;
    }
    return $args->next();
  }
}
