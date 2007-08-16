<?php
require_once 'support/simpletest.inc.php';
require_once '../examples/std.inc.php';
require_once 'support/mocks.inc.php';

class TestOfFormBehaviour extends UnitTestCase
{
  function test_initial_GET_request_should_init_form_memory_with_defaults() {
    $form = new MockFormBehaviour(new MockContext(), "");
    $form->descriptors = Array(
      Array("name" => "foo", "default" => "bar"),
    );
    $this->assertIsA($form->getMemoryObject(), "k_memory");
    $this->assertEqual(Array(), $form->getMemoryObject()->getFields());

    try {
      $form->execute();
    } catch (k_http_Response $response) { /* squelch */ }

    $this->assertEqual(Array('foo' => 'bar'), $form->getMemoryObject()->getFields());
  }

  function test_following_request_sould_not_init_again() {
    $form = new MockFormBehaviour(new MockContext(), "");
    $form->descriptors = Array(
      Array("name" => "foo", "default" => "bar"),
    );
    $this->assertIsA($form->getMemoryObject(), "k_memory");
    $this->assertEqual(Array(), $form->getMemoryObject()->getFields());

    try {
      $form->execute();
    } catch (k_http_Response $response) { /* squelch */ }

    // second request
    $form->execute();
    $this->pass();
  }

  function test_form_without_fields_shouldnt_loop_infinitely() {
    $form = new MockFormBehaviour(new MockContext(), "");

    try {
      $form->execute();
    } catch (k_http_Response $response) { /* squelch */ }

    $form->execute();
    $this->pass();
  }

  function test_descriptor_without_name_throws() {
    $form = new MockFormBehaviour(new MockContext(), "");
    $form->descriptors = Array(
      Array("foo" => "foo"),
    );
    try {
      try {
        $form->execute();
      } catch (k_http_Response $response) { /* squelch */ }
      $this->fail("Expected exception wasn't thrown");
    } catch (Exception $ex) {
      $this->pass("Caught expected exception");
    }
  }

  function test_no_descriptor_throws() {
    $form = new MockFormBehaviour(new MockContext(), "");
    $form->descriptors = NULL;
    try {
      try {
        $form->execute();
      } catch (k_http_Response $response) { /* squelch */ }
      $this->fail("Expected exception wasn't thrown");
    } catch (Exception $ex) {
      $this->pass("Caught expected exception");
    }
  }

  function test_descriptor_with_non_array_filter_throws() {
    $form = new MockFormBehaviour(new MockContext(), "");
    $form->descriptors = Array(
      Array("name" => "foo", "filters" => "strtolower"),
    );
    try {
      try {
        $form->execute();
      } catch (k_http_Response $response) { /* squelch */ }
      $this->fail("Expected exception wasn't thrown");
    } catch (Exception $ex) {
      $this->pass("Caught expected exception");
    }
  }

  function test_filter_is_applied_to_field() {
    $context = new MockContextWithFormValidation();
    $context->registry->ENV['K_HTTP_METHOD'] = "POST";
    $context->registry->POST['foo'] = "Lorem Ipsum";
    $form = new MockFormBehaviour($context, "");
    $form->descriptors = Array(
      Array("name" => "foo", "filters" => Array("strtolower")),
    );
    try {
      $form->execute();
    } catch (k_http_Response $response) { /* squelch */ }

    $this->assertEqual(Array('foo' => 'lorem ipsum'), $form->getMemoryObject()->getFields());
  }
}

class WebTestOfFormBehaviour extends ExtendedWebTestCase
{
  function test_web_case_available() {
    if (!$this->get($this->_baseUrl.'form/')) {
      $this->fail("failed connection to : " . $this->_baseUrl.'form/');
    }
  }

  function test_simple_form_usercase() {
    $this->get($this->_baseUrl.'form/');

    $this->assertField('email', '');
    $this->assertField('age', '');

    $this->clickSubmit();

    $this->assertField('email', '');
    $this->assertWantedPattern("/age must be numeric/i");
    $this->assertWantedPattern("/email is not a valid email-address/i");

    $this->setField('email', 'FOO@example.org');

    $this->clickSubmit();

    $this->assertField('email', 'foo@example.org');
    $this->assertWantedPattern("/age must be numeric/i");
    $this->assertWantedPattern("/emails doesn't match/i");

    // reset form
    $this->assertTrue($this->get($this->_baseUrl.'form/'));

    $this->assertField('email', '');
    $this->assertField('age', '');
  }

  function test_submit_form_with_valid_input() {
    $this->get($this->_baseUrl.'form/');

    $this->setField('email', 'foo@example.org');
    $this->setField('email_again', 'foo@example.org');
    $this->setField('age', '30');

    $this->clickSubmit();
    $this->assertWantedPattern("/'email' => 'foo@example\\.org'/i");
  }

  function test_form_transports_exotic_characters_undamaged() {
    $this->get($this->_baseUrl.'form/');

    // currently simpletest doesn't support utf-8, so this crude hack is nescesarry
    $this->setField('name', utf8_encode('iñtërnâtiônàlizætiøn "magic-quotes are evil"'));

    $this->clickSubmit();

    $this->assertField('name', utf8_encode('iñtërnâtiônàlizætiøn "magic-quotes are evil"'));
  }
}

simpletest_autorun(__FILE__);
