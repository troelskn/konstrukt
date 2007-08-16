<?php
require_once 'support/simpletest.inc.php';
require_once '../examples/std.inc.php';
require_once 'support/mocks.inc.php';

class TestOfResponse extends UnitTestCase
{
  function test_default_status_is_200() {
    $response = new k_http_Response();
    $this->assertEqual($response->status, 200);
  }

  function test_default_contenttype_is_html() {
    $response = new k_http_Response();
    $this->assertEqual($response->contentType, "text/html");
  }

  function test_default_charset_is_utf8() {
    $response = new k_http_Response();
    $this->assertEqual($response->encoding, "UTF-8");
  }

  function test_set_contenttype_header_updates_contenttype_property() {
    $response = new k_http_Response();
    $response->setHeader("Content-Type", "foo/bar");
    $this->assertTrue($response->contentType, "foo/bar");
    $this->assertFalse(in_array("Content-Type", array_keys($response->headers)));
  }

  function test_set_contenttype_header_with_charset_updates_contenttype_and_encoding_properties() {
    $response = new k_http_Response();
    $response->setHeader("Content-Type", "foo/bar; charset=yonks");
    $this->assertTrue($response->contentType, "foo/bar");
    $this->assertTrue($response->encoding, "yonks");
  }

  function test_set_header_overrides_previous_value() {
    $response = new k_http_Response();
    $response->setHeader("X-Test", "foo");
    $response->setHeader("X-Test", "bar");
    $this->assertEqual($response->headers["X-Test"], "bar");
  }

  function test_header_case_is_insensitive() {
    $response = new k_http_Response();
    $response->setHeader("X-Test", "foo");
    $response->setHeader("x-test", "bar");
    $this->assertEqual($response->headers["X-Test"], "bar");
  }

  function test_set_content_updates_content_property() {
    $response = new k_http_Response();
    $response->setContent("foo");
    $this->assertEqual($response->content, "foo");
  }

  function test_append_content_updates_content_property() {
    $response = new k_http_Response();
    $response->setContent("foo");
    $response->appendContent("bar");
    $this->assertEqual($response->content, "foobar");
  }
}

class WebTestOfResponse extends ExtendedWebTestCase
{
  function test_web_case_available() {
    if (!$this->get($this->_baseUrl.'response/')) {
      $this->fail("failed connection to : " . $this->_baseUrl.'response/');
    }
  }

  function test_force_download_pdf() {
    $this->get($this->_baseUrl.'response/download_pdf_legacy.php');
    $this->assertMime(array('application/pdf'));

    $this->get($this->_baseUrl.'response/download_pdf.php');
    $this->assertMime(array('application/pdf'));
  }

  function test_binary_safe_output() {
    $data = file_get_contents($this->_baseUrl.'response/output.php');
    $this->assertEqual("binary\0safe?", $data);
  }
}

simpletest_autorun(__FILE__);
