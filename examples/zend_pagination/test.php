<?php
error_reporting(E_ALL | E_STRICT);

// You need to have simpletest in your include_path
if (realpath($_SERVER['PHP_SELF']) == __FILE__) {
  require_once 'simpletest/autorun.php';
}
require_once '../../lib/konstrukt/virtualbrowser.inc.php';
require_once 'index.php';

class TestOfDummyCollection extends UnitTestCase {
  function test_first_element_is_Marian() {
    $coll = new DummyCollection();
    $first = $coll->current();
    $this->assertEqual("Marian", $first['first']);
  }
  function test_when_sorted_by_name_first_element_is_Foo() {
    $coll = new DummyCollection("first");
    $first = $coll->current();
    $this->assertEqual("Allan", $first['first']);
  }
}

class TestOfExampleZendPagination extends WebTestCase {
  function createBrowser() {
    return new k_VirtualSimpleBrowser('ZfPaginationPage');
  }
  function getDocument() {
    $doc = new DomDocument();
    @$doc->loadHtml($this->getBrowser()->getContent());
    return $doc;
  }
  function xselect($expression) {
    $xp = new DomXpath($this->getDocument());
    $result = $xp->query($expression);
    if ($result->length == 0) {
      return null;
    }
    if ($result->length > 1) {
      throw new Exception("More than one result returned.");
    }
    return $result->item(0)->nodeValue;
  }
  function assertNotNull($value, $message = '%s') {
    $dumper = new SimpleDumper();
    $message = sprintf(
      $message,
      '[' . $dumper->describeValue($value) . '] should not be null');
    return $this->assertTrue(isset($value), $message);
  }
  function test_root_is_accessible() {
    $this->assertTrue($this->get('/'));
    $this->assertResponse(200);
  }
  function test_first_page_shows_entries_1_through_10() {
    $this->assertTrue($this->get('/'));
    $this->assertResponse(200);
    $this->assertEqual("Marian", $this->xselect("//table/tbody/tr[1]/td[1]"));
    $this->assertEqual("Mackenzie", $this->xselect("//table/tbody/tr[10]/td[1]"));
  }
  function test_first_page_has_link_to_next_page() {
    $this->assertTrue($this->get('/'));
    $this->assertResponse(200);
    $this->assertLink("Next >");
  }
  function test_first_page_has_no_link_to_previous_page() {
    $this->assertTrue($this->get('/'));
    $this->assertResponse(200);
    $this->assertNoLink("< Previous");
  }
  function test_clicking_to_next_shows_entries_11_through_20() {
    $this->assertTrue($this->get('/'));
    $this->assertResponse(200);
    $this->clickLink("Next >");
    $this->assertEqual("Jennifer", $this->xselect("//table/tbody/tr[1]/td[1]"));
    $this->assertEqual("Trever", $this->xselect("//table/tbody/tr[10]/td[1]"));
  }
  function test_clicking_sort_by_first_shows_Allan_as_first_entry() {
    $this->assertTrue($this->get('/'));
    $this->assertResponse(200);
    $this->clickLink("first");
    $this->assertEqual("Allan", $this->xselect("//table/tbody/tr[1]/td[1]"));
  }
  function test_clicking_next_maintains_sort_order() {
    $this->assertTrue($this->get('/'));
    $this->assertResponse(200);
    $this->clickLink("first");
    $this->clickLink("Next >");
    $this->assertEqual("Cherie", $this->xselect("//table/tbody/tr[1]/td[1]"));
  }
  function test_clicking_sort_by_first_twice_shows_Vito_as_first_entry() {
    $this->assertTrue($this->get('/'));
    $this->assertResponse(200);
    $this->clickLink("first");
    $this->clickLink("first");
    $this->assertEqual("Vito", $this->xselect("//table/tbody/tr[1]/td[1]"));
  }
}