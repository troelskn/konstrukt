<?php
/**
 * A creator is a general factory, capable of creating instances of classes.
 */
interface k_wire_iCreator
{
  /**
   * Creates a transient (new) instance of a class.
   * Calling this multiple times, will return a new instance each time.
   * The function takes a variable number of arguments, after the classname. If the
   * factory needs userland arguments, these should be passed that way.
   * Alternatively, createArgs() can be used, if you want to pass the arguments as an array.
   * @param  $classname  string  Name of the class to create.
   * @param  [...]       mixed   Userland arguments to the factory.
   * @return mixed
   */
  function create($classname /* [, $arg1 [, $arg2 [ ... ]]] */);
  /**
   * Creates a transient (new) instance of a class, using an array of userland arguments.
   * Works like create(), but takes userland arguments as an array.
   * @param  $classname  string  Name of the class to create.
   * @param  $args       array   Array of userland arguments to the factory.
   * @return mixed
   */
  function createArgs($classname, $args);
}
