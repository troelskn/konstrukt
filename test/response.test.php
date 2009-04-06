<?php
error_reporting(E_ALL | E_STRICT);

// You need to have simpletest in your include_path
require_once 'simpletest/unit_tester.php';
if (realpath($_SERVER['PHP_SELF']) == __FILE__) {
  require_once 'simpletest/autorun.php';
}

require_once '../lib/konstrukt/konstrukt.inc.php';

class test_response_RootComponent extends k_Component {
  function map($name) {
    switch ($name) {
    case 'hello':
      return "test_response_HelloComponent";
    }
  }
  function renderHtml() {
    return "<p>Root</p>";
  }
  function wrapHtml($content) {
    return "<div class='wrap'>" . $content . "</div>";
  }
}

class test_response_HelloComponent extends k_Component {
  function renderHtml() {
    return "<p>Hello world</p>";
  }
}

class test_response_RootTwoComponent extends k_Component {
  function map($name) {
    switch ($name) {
    case 'json':
      return "test_response_JsonComponent";
    }
  }
  function wrapJson($content) {
    return array('content' => $content);
  }
}

class test_response_JsonComponent extends k_Component {
  function renderJson() {
    return array('message' => 'Hello World');
  }
}

class TestOfResponse extends UnitTestCase {
  function test_text_response_can_convert_to_text_string() {
    $t = new k_TextResponse("Lorem Ipsum");
    $this->assertEqual($t->toInternalRepresentation('text/text'), "Lorem Ipsum");
  }
  function test_text_response_can_convert_to_html_string() {
    $t = new k_TextResponse("Foo & Bar");
    $this->assertEqual($t->toInternalRepresentation('text/html'), "Foo &amp; Bar");
  }
  function test_html_response_can_convert_to_html_string() {
    $t = new k_HtmlResponse("<b>bold</b>");
    $this->assertEqual($t->toInternalRepresentation('text/html'), "<b>bold</b>");
  }
  function test_text_response_can_be_constructed_from_another_text_response() {
    $t1 = new k_TextResponse("Lorem Ipsum");
    $t = new k_TextResponse($t1);
    $this->assertEqual($t->toInternalRepresentation('text/text'), "Lorem Ipsum");
  }
  function test_text_response_can_be_constructed_from_multiple_text_responses() {
    $t1 = new k_TextResponse("Lorem Ipsum");
    $t2 = new k_TextResponse(" Dolor ");
    $t3 = new k_TextResponse("Sit Amet");
    $t = new k_TextResponse($t1, $t2, $t3);
    $this->assertEqual($t->toInternalRepresentation('text/text'), "Lorem Ipsum Dolor Sit Amet");
  }
  function test_html_response_can_be_constructed_from_a_text_response() {
    $t1 = new k_TextResponse("Foo & Bar");
    $h = new k_HtmlResponse($t1);
    $this->assertEqual($h->toInternalRepresentation('text/html'), "Foo &amp; Bar");
  }
  function test_html_response_can_be_constructed_from_mixed_responses() {
    $h1 = new k_HtmlResponse("<b>");
    $t1 = new k_TextResponse("Foo & Bar");
    $h2 = new k_HtmlResponse("</b>");
    $h = new k_HtmlResponse($h1, $t1, $h2);
    $this->assertEqual($h->toInternalRepresentation('text/html'), "<b>Foo &amp; Bar</b>");
  }
}

class TestOfResponseWrapping extends UnitTestCase {
  function test_wrap_doesnt_affect_self() {
    $glob = new k_adapter_MockGlobalsAccess(array(), array(), array('SERVER_NAME' => 'localhost', 'REQUEST_METHOD' => 'get'));
    $http = new k_HttpRequest('', '/', new k_DefaultIdentityLoader(), $glob);
    $components = new k_DefaultComponentCreator();
    $root = $components->create('test_response_RootComponent', $http);
    $r = $root->dispatch();
    $this->assertEqual($r->contentType(), 'text/html');
    $this->assertEqual($r->toInternalRepresentation($r->contentType()), "<p>Root</p>");
  }
  function test_legacy_string_level_wrapping() {
    $glob = new k_adapter_MockGlobalsAccess(array(), array(), array('SERVER_NAME' => 'localhost', 'REQUEST_METHOD' => 'get'));
    $http = new k_HttpRequest('', '/hello', new k_DefaultIdentityLoader(), $glob);
    $components = new k_DefaultComponentCreator();
    $root = $components->create('test_response_RootComponent', $http);
    $r = $root->dispatch();
    $this->assertEqual($r->contentType(), 'text/html');
    $this->assertEqual($r->toInternalRepresentation($r->contentType()), "<div class='wrap'><p>Hello world</p></div>");
  }
  function test_json_type_wrapping() {
    $glob = new k_adapter_MockGlobalsAccess(array(), array(), array('SERVER_NAME' => 'localhost', 'REQUEST_METHOD' => 'get'));
    $glob->headers = array('accept' => 'application/json');
    $http = new k_HttpRequest('', '/json', new k_DefaultIdentityLoader(), $glob);
    $components = new k_DefaultComponentCreator();
    $root = $components->create('test_response_RootTwoComponent', $http);
    $r = $root->dispatch();
    $this->assertEqual($r->contentType(), 'application/json');
    $this->assertEqual($r->toInternalRepresentation($r->contentType()), '{"content":{"message":"Hello World"}}');
  }
}