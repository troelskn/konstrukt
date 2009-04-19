<?php
/**
 * A locator can get a shared instance.
 */
interface wire_iLocator
{
  /**
   * Gets a shared instance of a class.
   * If the instance doesn't exist yet in the internal registry, it will be created.
   * A shared instance is unique for the container. It is still possible to create
   * multiple instances, but calling get() multiple times, will yield the same instance.
   * @param  $classname  string  Name of the class to retrieve.
   * @return mixed
   */
  function get($classname);
}
