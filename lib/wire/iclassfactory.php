<?php
interface wire_iClassFactory
{
  /**
   * Creates a new instance.
   */
  function createInstanceArgs($classname, wire_UserlandArguments $args);

  /**
   * Injects late dependencies (setter-dependencies) to an instance, previously created with createInstanceArgs()
   */
  function loadDependencies($instance, wire_UserlandArguments $args);
}
