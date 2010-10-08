<?php
error_reporting(E_ALL | E_STRICT);
set_include_path(__DIR__ . PATH_SEPARATOR . __DIR__ . '/../../lib/' . PATH_SEPARATOR . get_include_path());

// You need to have simpletest in your include_path
if (realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__) {
  require_once 'simpletest/autorun.php';
}
require_once '../../lib/konstrukt/virtualbrowser.inc.php';
require_once 'index.php';

class TestOfExampleContentTypes extends WebTestCase {
  function createBrowser() {
    return new k_VirtualSimpleBrowser('Root');
  }
  function test_root() {
    $this->assertTrue($this->get('/'));
    $this->assertResponse(200);
    $this->assertText("I feel good");
  }
  function test_xml_representation() {
    $this->addHeader('Accept: text/xml,*/*;q=0.8');
    $this->assertTrue($this->get('/'));
    $this->assertResponse(200);
    $this->assertMime('text/xml; charset=utf-8');
    $this->assertText("I feel good");
  }
}