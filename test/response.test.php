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

class test_response_RootThreeComponent extends k_Component {
  function map($name) {
    switch ($name) {
    case 'hello':
      return "test_response_HelloAgainComponent";
    }
  }
  function renderHtml() {
    return "<p>Root</p>";
  }
  function wrapHtml($content) {
    return "<div class='wrap'>" . $content . "</div>";
  }
}

class test_response_HelloAgainComponent extends k_Component {
  function execute() {
    $response = parent::execute();
    $response->setStatus(500);
    $response->setHeader('X-Foo', '42');
    return $response;
  }
  function renderHtml() {
    return "<p>Hello world</p>";
  }
}

class TestOfResponse extends UnitTestCase {
  function test_text_response_can_convert_to_text_string() {
    $t = new k_TextResponse("Lorem Ipsum");
    $this->assertEqual($t->toContentType('text/text'), "Lorem Ipsum");
  }
  function test_text_response_can_convert_to_html_string() {
    $t = new k_TextResponse("Foo & Bar");
    $this->assertEqual($t->toContentType('text/html'), "Foo &amp; Bar");
  }
  function test_html_response_can_convert_to_html_string() {
    $t = new k_HtmlResponse("<b>bold</b>");
    $this->assertEqual($t->toContentType('text/html'), "<b>bold</b>");
  }
  function test_text_response_can_be_constructed_from_another_text_response() {
    $t1 = new k_TextResponse("Lorem Ipsum");
    $t = new k_TextResponse($t1);
    $this->assertEqual($t->toContentType('text/text'), "Lorem Ipsum");
  }
  function test_html_response_can_be_constructed_from_a_text_response() {
    $t1 = new k_TextResponse("Foo & Bar");
    $h = new k_HtmlResponse($t1);
    $this->assertEqual($h->toContentType('text/html'), "Foo &amp; Bar");
  }
}

class TestOfResponseWrapping extends UnitTestCase {
  function test_wrap_doesnt_affect_self() {
    $glob = new k_adapter_MockGlobalsAccess(array(), array(), array('SERVER_NAME' => 'localhost', 'REQUEST_METHOD' => 'get'));
    $http = new k_HttpRequest('', '/', new k_DefaultIdentityLoader(), null, null, $glob);
    $components = new k_DefaultComponentCreator();
    $root = $components->create('test_response_RootComponent', $http);
    $r = $root->dispatch();
    $this->assertEqual($r->contentType(), 'text/html');
    $this->assertEqual($r->toContentType($r->contentType()), "<p>Root</p>");
  }
  function test_legacy_string_level_wrapping() {
    $glob = new k_adapter_MockGlobalsAccess(array(), array(), array('SERVER_NAME' => 'localhost', 'REQUEST_METHOD' => 'get'));
    $http = new k_HttpRequest('', '/hello', new k_DefaultIdentityLoader(), null, null, $glob);
    $components = new k_DefaultComponentCreator();
    $root = $components->create('test_response_RootComponent', $http);
    $r = $root->dispatch();
    $this->assertEqual($r->contentType(), 'text/html');
    $this->assertEqual($r->toContentType($r->contentType()), "<div class='wrap'><p>Hello world</p></div>");
  }
  function test_json_type_wrapping() {
    $glob = new k_adapter_MockGlobalsAccess(array(), array(), array('SERVER_NAME' => 'localhost', 'REQUEST_METHOD' => 'get'));
    $glob->headers = array('accept' => 'application/json');
    $http = new k_HttpRequest('', '/json', new k_DefaultIdentityLoader(), null, null, $glob);
    $components = new k_DefaultComponentCreator();
    $root = $components->create('test_response_RootTwoComponent', $http);
    $r = $root->dispatch();
    $this->assertEqual($r->contentType(), 'application/json');
    $this->assertEqual($r->toContentType($r->contentType()), '{"content":{"message":"Hello World"}}');
  }
  function test_wrapped_response_retains_headers() {
    $glob = new k_adapter_MockGlobalsAccess(array(), array(), array('SERVER_NAME' => 'localhost', 'REQUEST_METHOD' => 'get'));
    $http = new k_HttpRequest('', '/hello', new k_DefaultIdentityLoader(), null, null, $glob);
    $components = new k_DefaultComponentCreator();
    $root = $components->create('test_response_RootThreeComponent', $http);
    $r = $root->dispatch();
    $this->assertEqual($r->headers(), array('x-foo' => '42'));
    $this->assertEqual($r->toContentType($r->contentType()), "<div class='wrap'><p>Hello world</p></div>");
  }
  function test_wrapped_response_retains_status() {
    $glob = new k_adapter_MockGlobalsAccess(array(), array(), array('SERVER_NAME' => 'localhost', 'REQUEST_METHOD' => 'get'));
    $http = new k_HttpRequest('', '/hello', new k_DefaultIdentityLoader(), null, null, $glob);
    $components = new k_DefaultComponentCreator();
    $root = $components->create('test_response_RootThreeComponent', $http);
    $r = $root->dispatch();
    $this->assertEqual($r->status(), 500);
    $this->assertEqual($r->toContentType($r->contentType()), "<div class='wrap'><p>Hello world</p></div>");
  }
}

class TestOfJsonResponse extends UnitTestCase {
  function test_json_response_can_be_created_from_string() {
    $r = new k_JsonResponse("lorem ipsum");
    $this->assertIsA($r, 'k_JsonResponse');
  }
  function test_json_response_can_be_created_from_array() {
    $r = new k_JsonResponse(array("lorem ipsum", 42));
    $this->assertIsA($r, 'k_JsonResponse');
  }
}

class TestOfXmlResponse extends UnitTestCase {
  function test_xml_response_can_be_created_from_string() {
    $r = new k_XmlResponse("<foo/>");
    $this->assertIsA($r, 'k_XmlResponse');
  }
  function test_creating_xml_response_from_string_gets_a_default_charset() {
    $r = new k_XmlResponse("<foo/>");
    $this->assertNotNull($r->encoding());
  }
  function test_xml_response_can_be_created_from_simplexmlelement() {
    $r = new k_XmlResponse(new SimpleXMLElement("<foo/>"));
    $this->assertIsA($r, 'k_XmlResponse');
  }
  function test_xml_response_can_be_created_from_domdocument_loadxml() {
    $doc = new DOMDocument();
    $doc->loadXML("<foo/>");
    $r = new k_XmlResponse($doc);
    $this->assertIsA($r, 'k_XmlResponse');
  }
  function test_xml_response_can_be_created_from_domdocument_loadhtml() {
    $doc = new DOMDocument();
    $doc->loadHTML("<b>bold</b>");
    $r = new k_XmlResponse($doc);
    $this->assertIsA($r, 'k_XmlResponse');
  }
  function test_creating_an_xml_response_from_a_malformed_string_raises_an_error() {
    set_error_handler('k_exceptions_error_handler');
    try {
      new k_XmlResponse("<foo");
      $this->fail("Expected exception");
    } catch (exception $ex) {
      $this->pass();
    }
    restore_error_handler();
  }
  function test_xml_response_from_string_retains_xml_header() {
    $xml = "<" . "?xml version=\"1.0\" encoding=\"utf-8\"?" . ">" . "\n" . "<foo/>" . "\n";
    $r = new k_XmlResponse($xml);
    $this->assertEqual($r->toContentType('text/xml'), $xml);
  }
}
