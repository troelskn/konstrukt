<?php
/**
  * A combined singleton registry and general purpose class factory
  */
class k_Registry
{
  protected $__instances = Array();
  protected $__constructors = Array();
  protected $__aliases = Array();

  /**
    * Loads configuration from a file
    *
    */
  public function load($filename) {
    $constructors = Array();
    $aliases = Array();
    include($filename);
    foreach ($constructors as $className => $callback) {
      $this->registerConstructor($className, $callback);
    }
    foreach ($aliases as $alias => $classsName) {
      $this->registerAlias($alias, $classsName);
    }
  }

  /**
    * Magic method. Gets a singleton instance.
    *
    * @see get
    */
  public function __get($className) {
    return $this->get($className);
  }

  /**
    * Magic method. Tells whether a given property exists, or can be created on the fly.
    *
    * @see get
    */
  public function __isset($className) {
    $className = strtolower($className);
    if (isset($this->__aliases[$className])) {
      $className = $this->__aliases[$className];
    }
    return isset($this->__instances[$className]) || isset($this->__constructors[$className]) || class_exists($className);
  }

  /**
    * Returns a singleton instance of a class.
    *
    * If the instance hasn't been explicitly registered previously with set, it
    * will be created the first time it's requested.
    */
  public function get($className) {
    if (!is_string($className)) {
      throw new Exception("Type mismatch. First argument should be a string.");
    }
    $className = strtolower($className);
    if (isset($this->__aliases[$className])) {
      $className = $this->__aliases[$className];
    }
    if (!isset($this->__instances[$className])) {
      $this->__instances[$className] = $this->create($className);
    }
    return $this->__instances[$className];
  }

  /**
    * Sets an object as a singleton instance.
    *
    */
  public function set($className, $instance) {
    if (!is_string($className)) {
      throw new Exception("Type mismatch. First argument should be a string.");
    }
//    if (!is_object($instance)) {
//      throw new Exception("Type mismatch. Second argument should be an object.");
//    }
    $className = strtolower($className);
    $this->__instances[$className] = $instance;
  }

  /**
    * Create a new instance of the requested class.
    *
    */
  public function create($className /* [, $arg1 [, $arg2 [ ... ]]] */) {
    $args = func_get_args();
    array_shift($args);
    return $this->createArgs($className, $args);
  }

  /**
    * Create a new instance of the requested class with arguments as an array.
    *
    */
  public function createArgs($className, $args = Array()) {
    if (!is_string($className)) {
      throw new Exception("Type mismatch. First argument should be a string.");
    }
    $className = strtolower($className);
    if (isset($this->__aliases[$className])) {
      $className = $this->__aliases[$className];
    }
    if (isset($this->__constructors[$className])) {
      $constructor = $this->__constructors[$className];
    } else {
      $constructor = Array($this, 'defaultConstructor');
      $reflection = new ReflectionClass($className);
      while ($reflection) {
        $name = strtolower($reflection->getName());
        if (isset($this->__constructors[$name])) {
          $constructor = $this->__constructors[$name];
          break;
        }
        $reflection = $reflection->getParentClass();
      }
    }
    return call_user_func($constructor, $className, $args, $this);
  }

  /**
    * Registers a new factory for class.
    *
    */
  public function registerConstructor($className, $callback) {
    if (!is_string($className)) {
      throw new Exception("Type mismatch. First argument should be a string.");
    }
    if (!is_callable($callback)) {
      throw new Exception("Type mismatch. Second argument should be a valid callback.");
    }
    $this->__constructors[strtolower($className)] = $callback;
  }

  /**
    * Register an alias for a classname.
    *
    */
  public function registerAlias($alias, $classsName) {
    $alias = strtolower($alias);
    $this->__aliases[$alias] = strtolower($classsName);
  }

  /**
    * The default constructor.
    *
    */
  protected function defaultConstructor($className, $args, $registry) {
    $reflection = new ReflectionClass($className);
    if (count($args) > 0) {
      if (method_exists($reflection, 'newInstanceArgs')) {
        return $reflection->newInstanceArgs($args);
      } else {
        return call_user_func_array(Array($reflection, 'newInstance'), $args);
      }
    } else {
      return $reflection->newInstance();
    }
  }
}