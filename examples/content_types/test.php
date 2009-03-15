<?php
error_reporting(E_ALL | E_STRICT);

// You need to have simpletest in your include_path
if (realpath($_SERVER['PHP_SELF']) == __FILE__) {
  require_once 'simpletest/autorun.php';
}
require_once '../../lib/konstrukt/virtualbrowser.inc.php';
require_once 'index.php';

class TestOfExampleContentTypes extends WebTestCase {
  function createBrowser() {
    return new k_VirtualSimpleBrowser('MyMultiComponent');
  }
  function test_root() {
    $this->assertTrue($this->get('/'));
    $this->assertResponse(200);
    $this->assertText("hello in html");
  }
  function test_explicit_json_representation() {
    $this->assertTrue($this->get('/;json'));
    $this->assertResponse(200);
    $this->assertMime('application/json; charset=utf-8');
    $this->assertText("hello in json");
  }
  function test_negotiated_json_representation() {
    $this->addHeader('Accept: application/json,*/*;q=0.8');
    $this->assertTrue($this->get('/'));
    $this->assertResponse(200);
    $this->assertMime('application/json; charset=utf-8');
    $this->assertText("hello in json");
  }
}