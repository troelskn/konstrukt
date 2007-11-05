<?php
interface k_wire_iClassFactory
{
  /**
   * Creates a new instance.
   */
  function createInstanceArgs($classname, k_wire_UserlandArguments $args);

  /**
   * Injects late dependencies (setter-dependencies) to an instance, previously created with createInstanceArgs()
   */
  function loadDependencies($instance, k_wire_UserlandArguments $args);
}
