<?php
// You need to have simpletest in your include_path
if (realpath($_SERVER['PHP_SELF']) == __FILE__) {
  require_once 'simpletest/autorun.php';
}
require_once dirname(__FILE__) . '/../../config/global.inc.php';
require_once 'konstrukt/trunk/lib/konstrukt/virtualbrowser.inc.php';

class WebTestOfRoot extends WebTestCase {
  function createBrowser() {
    return new k_VirtualSimpleBrowser('components_Root', new k_PhemtoAdapter(create_phemto_container()));
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
