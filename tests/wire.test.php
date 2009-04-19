<?php
function test_exceptions_error_handler($severity, $message, $filename, $lineno) {
  if (error_reporting() == 0) {
    return;
  }
  if (error_reporting() & $severity) {
    throw new ErrorException($message, 0, $severity, $filename, $lineno);
  }
}

/**
  * Provides functionality for including files.
  */
class test_ClassLoader
{
  /**
    * Default autoloader for Konstrukt naming scheme.
    */
  static function autoload($classname) {
    $filename = str_replace('_', '/', strtolower($classname)).'.php';
    if (self::SearchIncludePath($filename)) {
      require_once($filename);
    }
  }

  /**
    * Searches the include-path for a filename.
    * Returns the absolute path (realpath) if found or FALSE
    * @return mixed
    */
  static function SearchIncludePath($filename) {
    if (is_file($filename)) {
      return $filename;
    }
    foreach (explode(PATH_SEPARATOR, ini_get("include_path")) as $path) {
      if (strlen($path) > 0 && $path{strlen($path)-1} != DIRECTORY_SEPARATOR) {
        $path .= DIRECTORY_SEPARATOR;
      }
      $f = realpath($path . $filename);
      if ($f && is_file($f)) {
        return $f;
      }
    }
    return FALSE;
  }
}

// You need to have simpletest in your include_path
require_once 'simpletest/unit_tester.php';
require_once 'simpletest/mock_objects.php';
if (realpath($_SERVER['PHP_SELF']) == __FILE__) {
  // Adds /lib/ to the path
  ini_set(
    'include_path',
    "lib"
    . PATH_SEPARATOR . dirname(dirname(__FILE__))."/lib"
    . PATH_SEPARATOR . ini_get('include_path'));
  error_reporting(E_ALL | E_STRICT);
  set_error_handler('test_exceptions_error_handler');
  spl_autoload_register(Array('test_ClassLoader', 'autoload'));
  require_once 'simpletest/autorun.php';
}

/*
todo:
  preventLocatorPropagation -- is this really needed?
  are aliases properly tested?
  am i satisfied with having __get() in the mix?
    -- maybe add an option to throw on usage (like preventLocatorPropagation)
  should defaultfactory be replacable? (i think not)
  re-thrown exceptions?
*/

class TestOfWire_Foo
{
  public $bar;

  function __construct($bar) {
    $this->bar = $bar;
  }
}

class TestOfWire_Bar
{
  public $foo;

  function setFoo($foo) {
    $this->foo = $foo;
  }
}

class TestOfWire_Cux
{
}

class TestOfWire_SubCux extends TestOfWire_Cux
{
}

class TestOfWire extends UnitTestCase
{
  function setUp() {
    $this->container = new wire_Container();
  }

  function tearDown() {
    $this->container = NULL;
  }

  function TestOfWire_create_class_instance() {
    $this->assertIsA($this->container->create('testofwire_cux'), 'testofwire_cux');
  }

  function test_get_shared_instance_of_class() {
    $this->assertTrue($this->container->get('testofwire_cux') === $this->container->get('testofwire_cux'));
  }

  function test_create_instance_with_constructor_dependency() {
    $factory = $this->container->register('testofwire_foo');
    $factory->registerConstructor(new wire_SharedDependency('testofwire_cux'));
    $foo = $this->container->create('testofwire_foo');
    $this->assertIsA($foo, 'testofwire_foo');
    $this->assertIsA($foo->bar, 'testofwire_cux');
  }

  function test_create_instance_with_setter_dependency() {
    $factory = $this->container->register('testofwire_bar');
    $factory->registerSetter(new wire_SharedDependency('testofwire_cux'), 'setFoo');
    $bar = $this->container->create('testofwire_bar');
    $this->assertIsA($bar, 'testofwire_bar');
    $this->assertIsA($bar->foo, 'testofwire_cux');
  }

  function test_create_instance_with_property_dependency() {
    $factory = $this->container->register('testofwire_bar');
    $factory->registerProperty(new wire_SharedDependency('testofwire_cux'), 'cuxx');
    $bar = $this->container->create('testofwire_bar');
    $this->assertTrue(isset($bar->cuxx));
    $this->assertIsA($bar->cuxx, 'testofwire_cux');
  }

  function test_create_instance_with_cyclic_dependency_using_setter() {
    $factory = $this->container->register('testofwire_foo');
    $factory->registerConstructor(new wire_SharedDependency('testofwire_bar'));

    $factory = $this->container->register('testofwire_bar');
    $factory->registerSetter(new wire_SharedDependency('testofwire_foo'), 'setFoo');

    $foo = $this->container->get('testofwire_foo');
    $bar = $this->container->get('testofwire_bar');

    $this->assertReference($foo, $bar->foo);
    $this->assertReference($bar, $foo->bar);
  }

  function test_get_shared_instance_with_cyclic_property_dependency() {
    $factory = $this->container->register('testofwire_cux');
    $factory->registerProperty(new wire_SharedDependency('testofwire_cux'), 'cuxx');
    $cux = $this->container->get('testofwire_cux');
    $this->assertReference($cux, $cux->cuxx);
  }

  function test_registered_factory_called_when_creating_subclass() {
    $factory = $this->container->register('testofwire_cux');
    $factory->registerProperty(new wire_ConstantDependency('some value'), 'cuxx');

    $cux = $this->container->create('testofwire_subcux');
    $this->assertIsA($cux, 'testofwire_subcux');
    $this->assertTrue(isset($cux->cuxx));
  }

  function test_create_with_required_userland_dependency() {
    $factory = $this->container->register('testofwire_foo');
    $factory->registerConstructor(new wire_RequiredUserDependency());
    $obj = new StdClass();
    $foo = $this->container->create('testofwire_foo', $obj);
    $this->assertReference($foo->bar, $obj);
  }

  function test_create_with_required_userland_dependency_fails_when_called_without_arguments() {
    $factory = $this->container->register('testofwire_foo');
    $factory->registerConstructor(new wire_RequiredUserDependency());
    try {
      $this->container->create('testofwire_foo');
      $this->fail("Expected exception not thrown");
    } catch (wire_Exception $ex) {
      $this->pass("Expected exception caught");
    }
  }

  function test_create_with_optional_userland_dependency_doesnt_fail_when_called_without_arguments() {
    $factory = $this->container->register('testofwire_foo');
    $factory->registerConstructor(new wire_UserDependency(NULL));
    $this->container->create('testofwire_foo');
  }

  function test_create_with_multiple_required_userland_dependency() {
    $factory = $this->container->register('testofwire_cux');
    $factory->registerProperty(new wire_RequiredUserDependency(), 'one');
    $factory->registerProperty(new wire_RequiredUserDependency(), 'two');
    $one = new StdClass();
    $two = new StdClass();
    $foo = $this->container->create('testofwire_cux', $one, $two);
    $this->assertReference($foo->one, $one);
    $this->assertReference($foo->two, $two);
  }

  function test_cascade_userland_arguments() {
    $factory = $this->container->register('testofwire_foo');
    $factory->registerConstructor(new wire_TransientDependency('testofwire_cux'));
    $factory = $this->container->register('testofwire_cux');
    $factory->registerProperty(new wire_RequiredUserDependency(), 'prop');
    $one = new StdClass();
    $foo = $this->container->create('testofwire_foo', $one);
    $this->assertReference($foo->bar->prop, $one);
  }

  function test_cascade_userland_arguments_with_multiple_consumers() {
    $factory = $this->container->register('testofwire_foo');
    $factory->registerConstructor(new wire_TransientDependency('testofwire_cux'));
    $factory->registerProperty(new wire_RequiredUserDependency(), 'prop');
    $factory = $this->container->register('testofwire_cux');
    $factory->registerProperty(new wire_RequiredUserDependency(), 'prop');
    $one = new StdClass();
    $two = new StdClass();
    $foo = $this->container->create('testofwire_foo', $one, $two);
    $this->assertReference($foo->prop, $one);
    $this->assertReference($foo->bar->prop, $two);
  }
}


/* legacy */

class TestOfRegistry_Foo
{
  public $cargs;

  function __construct() {
    $this->cargs = func_get_args();
  }

  function yonks() {
    return "yonks";
  }
}

class TestOfRegistry_FooRef
{
  public $ref;

  function __construct(&$ref) {
    $this->ref =& $ref;
  }
}

class TestOfRegistry_SubFoo extends TestOfRegistry_Foo
{
}

class TestOfRegistry extends UnitTestCase
{
  function test_can_create_class_without_args() {
    $registry = new wire_Container();
    $foo = $registry->create("TestOfRegistry_Foo");
    $this->assertIsA($foo, "TestOfRegistry_Foo");
  }

  function test_can_create_class_with_args() {
    $registry = new wire_Container();
    $foo = $registry->create("TestOfRegistry_Foo", 42);
    $this->assertEqual(Array(42), $foo->cargs);
  }

  function test_can_create_class_with_args_as_array() {
    $registry = new wire_Container();
    $foo = $registry->createArgs("TestOfRegistry_Foo", Array(42));
    $this->assertEqual(Array(42), $foo->cargs);
  }

  // segfaults ... and doesn't work either
  // the problem is, that calling ReflectionClass::newInstance() with a constructor, taking arguments by reference
  // function test_create_class_with_args_as_array_preserves_reference() {
  //   $registry = new wire_Container();
  //   $val = 42;
  //   $args = Array();
  //   $args[] =& $val;
  //   $foo = $registry->createArgs("TestOfRegistry_FooRef", $args);
  //   $this->assertEqual($val, $foo->ref);
  //   $this->assertReference($val, $foo->ref);
  // }

  function test_register_invalid_callback_as_factory_throws_exception() {
    $registry = new wire_Container();
    try {
      $registry->registerConstructor(
        "TestOfRegistry_Foo",
        'not_a_callback'
      );
      $this->fail('Expected exception not thrown.');
    } catch (Exception $ex) {
      if ($ex->getMessage() == "Type mismatch. Second argument should be a valid callback.") {
        $this->pass();
      } else {
        throw $ex;
      }
    }
  }

  function test_registered_factory_called_when_creating_class() {
    $registry = new wire_Container();
    $registry->registerConstructor(
      "TestOfRegistry_Foo",
      create_function(
        '$className, $args, $registry',
        'return new StdClass();'
      )
    );
    $foo = $registry->create("TestOfRegistry_Foo");
    $this->assertIsA($foo, "StdClass");
  }

  function test_registered_factory_called_when_creating_subclass() {
    $registry = new wire_Container();
    $registry->registerConstructor(
      "TestOfRegistry_Foo",
      create_function(
        '$className, $args, $registry',
        '$foo = new StdClass(); $foo->cargs = func_get_args(); return $foo;'
      )
    );
    $foo = $registry->create("TestOfRegistry_SubFoo");
    $this->assertIsA($foo, "StdClass");
  }

  function test_callback_pass_classname_as_first_parameter() {
    $registry = new wire_Container();
    $registry->registerConstructor(
      "TestOfRegistry_Foo",
      create_function(
        '$className, $args, $registry',
        '$foo = new StdClass(); $foo->cargs = func_get_args(); return $foo;'
      )
    );
    $foo = $registry->create("TestOfRegistry_SubFoo", 'qux');
    $this->assertEqual($foo->cargs[0], strtolower('TestOfRegistry_SubFoo'));
  }

  function test_callback_pass_arguments_as_second_parameter() {
    $registry = new wire_Container();
    $registry->registerConstructor(
      "TestOfRegistry_Foo",
      create_function(
        '$className, $args, $registry',
        '$foo = new StdClass(); $foo->cargs = func_get_args(); return $foo;'
      )
    );
    $foo = $registry->create("TestOfRegistry_SubFoo", 'qux');
    $this->assertEqual($foo->cargs[1], Array('qux'));
  }

  function test_callback_pass_registry_as_third_parameter() {
    $registry = new wire_Container();
    $registry->registerConstructor(
      "TestOfRegistry_Foo",
      create_function(
        '$className, $args, $registry',
        '$foo = new StdClass(); $foo->cargs = func_get_args(); return $foo;'
      )
    );
    $foo = $registry->create("TestOfRegistry_SubFoo", 'qux');
    $this->assertReference($foo->cargs[2], $registry);
  }

  function test_get_shared_returns_new_instance() {
    $registry = new wire_Container();
    $foo = $registry->get("TestOfRegistry_Foo");
    $this->assertIsA($foo, "TestOfRegistry_Foo");
  }

  function test_get_shared_twice_returns_same_instance() {
    $registry = new wire_Container();
    $foo = $registry->get("TestOfRegistry_Foo");
    $bar = $registry->get("TestOfRegistry_Foo");
    $this->assertReference($foo, $bar);
  }

  function test_alias_resolves_to_classname() {
    $registry = new wire_Container();
    $registry->registerAlias("foo", "TestOfRegistry_Foo");
    $foo = $registry->get('foo');
    $bar = $registry->get("TestOfRegistry_Foo");
    $this->assertReference($foo, $bar);
  }

  function test_get_shared_with_magic_stuff() {
    $registry = new wire_Container();
    $registry->registerAlias("foo", "TestOfRegistry_Foo");
    $foo = $registry->foo;
    $bar = $registry->get("TestOfRegistry_Foo");
    $this->assertReference($foo, $bar);
  }

  function test_get_shared_with_magic_stuff_works_transparently() {
    $registry = new wire_Container();
    $registry->registerAlias("foo", "TestOfRegistry_Foo");
    $this->assertEqual($registry->foo->yonks(), "yonks");
  }

  function test_load_from_config_file() {
    $registry = new wire_Container();
    $registry->load(dirname(__FILE__)."/support/registry.config.php");
    $this->assertIsA($registry->get("TestOfRegistry_Foo"), "StdClass");
    $this->assertIsA($registry->get("yabba_the_hutt"), "StdClass");
  }

}

