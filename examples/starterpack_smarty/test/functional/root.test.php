<?php
// You need to have simpletest in your include_path
if (realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__) {
  require_once 'simpletest/autorun.php';
}
require_once dirname(__FILE__) . '/../../config/global.inc.php';
require_once 'konstrukt/virtualbrowser.inc.php';

class WebTestOfRoot extends WebTestCase {
  protected $directory;
  function setUp() {
    $_SESSION = array();
    $this->directory = dirname(__FILE__) . '/../../templates_c/';
    if (!is_dir($this->directory)) {
        mkdir($this->directory);
    }
  }
  function tearDown() {
    unset($_SESSION);
    $this->rmdir($this->directory);
  }
  function rmdir($dir) {
    if (is_dir($dir)) {
      $objects = scandir($dir);
      foreach ($objects as $object) {
        if ($object != "." && $object != "..") {
          if (filetype($dir."/".$object) == "dir") {
            $this->rmdir($dir."/".$object);
          } else {
            unlink($dir."/".$object);
          }
        }
      }
      reset($objects);
      rmdir($dir);
    }
  }
  function createBrowser() {
    $this->container = create_container();
    return new k_VirtualSimpleBrowser('components_Root', new k_InjectorAdapter($this->container));
  }
  function createInvoker() {
    return new SimpleInvoker($this);
  }
  function test_root() {
    $this->assertTrue($this->get('/'));
    $this->assertResponse(200);
    $this->assertText("This page is intentionally left blank");
  }
}
