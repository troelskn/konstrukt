<?php
/**
 * A userland suppliable value, which can be left out, in which case a default is applied.
 * This will only pop a userland argument, if any are left. Otherwise, a default (constant)
 * value is supplied. In behaviour, it's a mix of k_wire_RequiredUserDependency and k_wire_ConstantDependency
 */
class k_wire_UserDependency extends k_wire_RequiredUserDependency
{
  protected $defaultValue;

  /**
   * @param  $value  mixed  The default avalue to fall back on, if no argument is supplied.
   */
  function __construct($defaultValue = NULL) {
    $this->defaultValue = $defaultValue;
  }

  function resolve(k_wire_iLocator $locator, k_wire_UserlandArguments $args) {
    if (!$args->hasNext()) {
      return $this->defaultValue;
    }
    return $args->next();
  }
}
