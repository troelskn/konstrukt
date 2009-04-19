<?php
/**
 * A highly flexible factory, for creating instances of classes.
 */
class wire_ConfigurableFactory implements wire_iClassFactory
{
  protected $locator;
  protected $early = array();
  protected $late = array();

  /**
   * @param  $locator  wire_iLocator  A locator, from which to retrieve dependencies.
   */
  function __construct(wire_iLocator $locator) {
    $this->locator = $locator;
  }

  /**
   * Creates a new instance.
   */
  function createInstanceArgs($classname, wire_UserlandArguments $args) {
    return call_user_func_array(array(new ReflectionClass($classname), 'newInstance'), $this->resolveEarly($args));
  }

  /**
   * Injects late dependencies (setter-dependencies) to an instance, previously created with createInstanceArgs()
   */
  function loadDependencies($instance, wire_UserlandArguments $args) {
    foreach ($this->late as $dependency) {
      $value = $dependency['source']->resolve($this->locator, $args);
      switch ($dependency['target']) {
      case 'setter':
        if (!method_exists($instance, $dependency['methodname'])) {
          throw new wire_Exception(sprintf("Registered setter doesn't exist '%s' on instance of '%s'", $dependency['methodname'], get_class($instance)));
        }
        call_user_func(array($instance, $dependency['methodname']), $value);
        break;
      case 'property':
        $propertyname = $dependency['propertyname'];
        $instance->$propertyname = $value;
        break;
      default:
        throw new wire_Exception(sprintf("Unknown target '%s'", $dependency['target']));
      }
    }
    return $instance;
  }

  /**
   * Register a dependency for injection through the constructor.
   */
  function registerConstructor(wire_iDependencySource $source) {
    $this->early[] = array(
      'target' => 'constructor',
      'source' => $source,
    );
  }

  /**
   * Register a dependency for injection through a setter method.
   */
  function registerSetter(wire_iDependencySource $source, $methodname) {
    $this->late[] = array(
      'target' => 'setter',
      'source' => $source,
      'methodname' => $methodname,
    );
  }

  /**
   * Register a dependency for injection through a public property.
   */
  function registerProperty(wire_iDependencySource $source, $propertyname) {
    $this->late[] = array(
      'target' => 'property',
      'source' => $source,
      'propertyname' => $propertyname,
    );
  }

  protected function resolveEarly(wire_UserlandArguments $args) {
    $result = array();
    foreach ($this->early as $dependency) {
      $result[] = $dependency['source']->resolve($this->locator, $args);
    }
    return $result;
  }
}
