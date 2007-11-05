<?php
/**
 * A representation of a dependency source.
 * A source is able to resolve a dependency. How this is delivered to the consumer
 * is irellevant for the source.
 */
interface k_wire_iDependencySource
{
  /**
   * Resolves the dependency and returns it.
   * @param   $locator  k_wire_iLocator            A locator, from which dependencies can be retrieved.
   * @param   $args     k_wire_UserlandArguments   Userland arguments, supplied at calltime, which may be used in the resolve.
   * @returns mixed
   */
  function resolve(k_wire_iLocator $locator, k_wire_UserlandArguments $args);
}
