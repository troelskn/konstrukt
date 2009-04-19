<?php
/**
 * A representation of a dependency source.
 * A source is able to resolve a dependency. How this is delivered to the consumer
 * is irellevant for the source.
 */
interface wire_iDependencySource
{
  /**
   * Resolves the dependency and returns it.
   * @param   $locator  wire_iLocator            A locator, from which dependencies can be retrieved.
   * @param   $args     wire_UserlandArguments   Userland arguments, supplied at calltime, which may be used in the resolve.
   * @returns mixed
   */
  function resolve(wire_iLocator $locator, wire_UserlandArguments $args);
}
