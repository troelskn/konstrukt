<?php
require_once('../examples/std.inc.php');

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
  function TestOfRegistry() {
    $this->UnitTestCase();
  }

  function setUp() {
    $this->old_error_reporting = error_reporting(E_ALL);
  }

  function tearDown() {
    error_reporting($this->old_error_reporting);
  }

  function test_can_create_class_without_args() {
    $registry = new k_Registry();
    $foo = $registry->create("TestOfRegistry_Foo");
    $this->assertIsA($foo, "TestOfRegistry_Foo");
  }

  function test_can_create_class_with_args() {
    $registry = new k_Registry();
    $foo = $registry->create("TestOfRegistry_Foo", 42);
    $this->assertEqual(Array(42), $foo->cargs);
  }

  function test_can_create_class_with_args_as_array() {
    $registry = new k_Registry();
    $foo = $registry->createArgs("TestOfRegistry_Foo", Array(42));
    $this->assertEqual(Array(42), $foo->cargs);
  }

  function test_create_class_with_args_doesnt_preserve_reference() {
    $registry = new k_Registry();
    $val = 42;
    $foo = $registry->create("TestOfRegistry_FooRef", $val);
    return; // fixme
    $this->assertEqual($val, $foo->ref);
    $this->assertCopy($val, $foo->ref);
  }

  function test_create_class_with_args_as_array_preserves_reference() {
    $registry = new k_Registry();
    $val = 42;
    $args = Array();
    $args[] =& $val;
    $foo = $registry->createArgs("TestOfRegistry_FooRef", $args);
    $this->assertEqual($val, $foo->ref);
    $this->assertReference($val, $foo->ref);
  }

  function test_register_invalid_callback_as_factory_throws_exception() {
    $registry = new k_Registry();
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
    $registry = new k_Registry();
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
    $registry = new k_Registry();
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
    $registry = new k_Registry();
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
    $registry = new k_Registry();
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
    $registry = new k_Registry();
    $registry->registerConstructor(
      "TestOfRegistry_Foo",
      create_function(
        '$className, $args, $registry',
        '$foo = new StdClass(); $foo->cargs = func_get_args(); return $foo;'
      )
    );
    $foo = $registry->create("TestOfRegistry_SubFoo", 'qux');
    $this->assertEqual($foo->cargs[2], $registry);
  }

  function test_get_singleton_returns_new_instance() {
    $registry = new k_Registry();
    $foo = $registry->get("TestOfRegistry_Foo");
    $this->assertIsA($foo, "TestOfRegistry_Foo");
  }

  function test_get_singleton_twice_returns_same_instance() {
    $registry = new k_Registry();
    $foo = $registry->get("TestOfRegistry_Foo");
    $bar = $registry->get("TestOfRegistry_Foo");
    $this->assertReference($foo, $bar);
  }

  function test_get_singleton_with_magic_stuff() {
    $registry = new k_Registry();
    $registry->registerAlias("foo", "TestOfRegistry_Foo");
    $foo = $registry->foo;
    $bar = $registry->get("TestOfRegistry_Foo");
    $this->assertReference($foo, $bar);
  }

  function test_get_singleton_with_magic_stuff_works_transparently() {
    $registry = new k_Registry();
    $registry->registerAlias("foo", "TestOfRegistry_Foo");
    $this->assertEqual($registry->foo->yonks(), "yonks");
  }

  function test_load_from_config_file() {
    $registry = new k_Registry();
    require_once '../examples/std.inc.php';
    $registry->load(dirname(__FILE__)."/registry.config.php");
    $this->assertIsA($registry->get("TestOfRegistry_Foo"), "StdClass");
    $this->assertIsA($registry->get("yabba_the_hutt"), "StdClass");
  }
}
