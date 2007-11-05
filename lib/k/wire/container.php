<?php
/**
 * A dependency injection container.
 * This is the primary interface to wire. An application would typically have just
 * one instance of k_wire_Container.
 */
class k_wire_Container implements k_wire_iLocator, k_wire_iCreator
{
  protected $registry;
  protected $emptyArguments;
  protected $defaultFactory;
  /**
   * @var [] k_wire_iClassFactory
   */
  protected $factories = array();
  protected $aliases = array();
  protected $preventLocatorPropagation = FALSE;

  protected $pending = array();
  protected $call_stack = 0;

  function __construct() {
    $this->registry = new k_wire_Registry();
    $this->registry->set('k_wire_Creator', new k_wire_Creator($this));
    $this->emptyArguments = new k_wire_UserlandArguments();
    $this->defaultFactory = new k_wire_DefaultFactory();
  }

  /**
   * Turns on/off preventLocatorPropagation.
   * When this setting is on, it isn't possible to propagate the locator itself.
   * You can turn on this setting, to enforce a stricter decoupling of dependencies.
   * @param  $prevent  boolean
   */
  function preventLocatorPropagation($prevent = TRUE) {
    $this->preventLocatorPropagation = !!$prevent;
  }

  /**
   * Stores an instance in the registry.
   * @deprecated
   */
  function set($classname, $instance) {
    return $this->registry->set($classname, $instance);
  }

  function __get($classname) {
    return $this->get($classname);
  }

  function __isset($classname) {
    return $this->registry->has($classname);
  }

  function get($classname) {
    $classname = $this->normaliseAlias($classname);
    if ($this->preventLocatorPropagation && is_a($classname, 'k_wire_iLocator')) {
      throw new k_wire_LocatorPropagationException();
    }
    $this->call_stack++;
    try {
      if (!$this->registry->has($classname)) {
        $factory = $this->findFactory($classname);
        $instance = $factory->createInstanceArgs($classname, $this->emptyArguments);
        $this->registry->set($classname, $instance);
        $this->pending[] = array($factory, $instance, $this->emptyArguments, $this->call_stack);
      }
    } catch (Exception $ex) {
      // unwind protect
      $this->call_stack--;
      throw $ex;
    }
    $this->call_stack--;
    if ($this->call_stack == 0) {
      $this->resolveLateDependencies();
    }
    return $this->registry->get($classname);
  }

  function create($classname) {
    $func_get_args = func_get_args();
    array_shift($func_get_args);
    return $this->createArgs($classname, $func_get_args);
  }

  function createArgs($classname, $args) {
    $classname = $this->normaliseAlias($classname);
    if ($this->preventLocatorPropagation && is_a($classname, 'k_wire_iLocator')) {
      throw new k_wire_LocatorPropagationException();
    }
    $this->call_stack++;
    try {
      if (!$args instanceOf k_wire_UserlandArguments) {
        $args = new k_wire_UserlandArguments($args);
      }
      $factory = $this->findFactory($classname);
      $instance = $factory->createInstanceArgs($classname, $args);
      $this->pending[] = array($factory, $instance, $args, $this->call_stack);
    } catch (Exception $ex) {
      // unwind protect
      $this->call_stack--;
      throw $ex;
    }
    $this->call_stack--;
    if ($this->call_stack == 0) {
      $this->resolveLateDependencies();
    }
    return $instance;
  }

  /**
   * Registers a factory for a class.
   * @param  $classname  string                Name of the class.
   * @param  $factory    k_wire_iClassFactory  The factory to use for cvreating instances of this class.
   */
  function registerFactory($classname, k_wire_iClassFactory $factory) {
    $this->factories[strtolower($classname)] = $factory;
    return $factory;
  }

  /**
   * Creates a new configurable factory for a class.
   * @param  $classname  string  Name of the class.
   */
  function register($classname) {
    return $this->registerFactory($classname, new k_wire_ConfigurableFactory($this));
  }

  /**
    * Registers a callback factory for a class.
    * @param  $classname  string    Name of the class.
    * @param  $callback   callback  A callback to instantiate the class.
    * @deprecated
    */
  function registerConstructor($classname, $callback) {
    if (!is_string($classname)) {
      throw new Exception("Type mismatch. First argument should be a string.");
    }
    return $this->registerFactory($classname, new k_wire_CallbackFactory($this, $callback));
  }

  /**
    * Loads configuration from a file.
    * @param  $filename  string
    * @deprecated
    */
  function load($filename) {
    $constructors = array();
    $aliases = array();
    include($filename);
    foreach ($constructors as $className => $callback) {
      $this->registerConstructor($className, $callback);
    }
    foreach ($aliases as $alias => $classsName) {
      $this->registerAlias($alias, $classsName);
    }
  }

  /**
    * Register an alias for a classname.
    */
  function registerAlias($alias, $classsname) {
    $this->aliases[strtolower($alias)] = strtolower($classsname);
  }

  protected function normaliseAlias($classname) {
    $classname = strtolower($classname);
    if (isset($this->aliases[$classname])) {
      return $this->aliases[$classname];
    }
    return $classname;
  }

  protected function resolveLateDependencies() {
    $pending = $this->pending;
    $this->pending = array();
    usort($pending, array($this, 'sortLateDependencies'));
    foreach ($pending as $late) {
      $late[0]->loadDependencies($late[1], $late[2]);
    }
  }

  protected function sortLateDependencies($a, $b) {
    if ($a[3] == $b[3]) {
      return 0;
    }
    return $a[3] > $b[3] ? 1 : -1;
  }

  /**
   * Returns the best suitable factory for a class
   * @returns k_wire_iClassFactory
   */
  protected function findFactory($classname) {
    if (isset($this->factories[$classname])) {
      return $this->factories[$classname];
    }
    $factory = NULL;
    $reflection = new ReflectionClass($classname);
    while ($reflection) {
      $name = strtolower($reflection->getName());
      if (isset($this->factories[$name])) {
        $factory = $this->factories[$name];
        break;
      }
      $reflection = $reflection->getParentClass();
    }
    if ($factory) {
      return $factory;
    }
    return $this->defaultFactory;
  }
}
