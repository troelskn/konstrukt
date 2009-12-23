<?php
error_reporting(E_ALL | E_STRICT);

// You need to have simpletest in your include_path
require_once 'simpletest/unit_tester.php';
if (realpath($_SERVER['PHP_SELF']) == __FILE__) {
  require_once 'simpletest/autorun.php';
}

require_once '../lib/konstrukt/konstrukt.inc.php';
require_once 'support/mocks.inc.php';

class TestOfGlobalsAccess extends UnitTestCase {
  function setUp() {
    $this->GET = $_GET;
    $_GET = array();
  }
  function tearDown() {
    $_GET = $this->GET;
  }
  function test_undo_magic_quotes_if_present() {
    $g = new k_adapter_SafeGlobalsAccess(new k_charset_Latin1CharsetStrategy(), true);
    $_GET = array(
      'name' => "O\\'Reilly"
    );
    $this->assertEqual($g->query(), array('name' => "O'Reilly"));
  }
  function test_doesnt_undo_magic_quotes_if_not_present() {
    $g = new k_adapter_SafeGlobalsAccess(new k_charset_Latin1CharsetStrategy(), false);
    $_GET = array(
      'name' => "O\\'Reilly"
    );
    $this->assertEqual($g->query(), array('name' => "O\\'Reilly"));
  }
  function test_unmagic_on_deep_array_doesnt_crash_runtime() {
    $g = new k_adapter_SafeGlobalsAccess(new k_charset_Latin1CharsetStrategy(), true);
    eval("\$_GET = " . str_repeat("array(", 1024) . "\"O\\\\'Reilly\"" . str_repeat(")", 1024) . ";");
    $g->query(); // Note: A failing test will produce a Fatal Error and halt the suite
  }
  function test_mixed_case_key_doesnt_get_conflated() {
    $g = new k_adapter_SafeGlobalsAccess(new k_charset_Latin1CharsetStrategy(), true);
    $_GET = array(
      'foo' => "42",
      'Foo' => "1337"
    );
    $this->assertEqual($g->query(), array('foo' => "42", 'Foo' => "1337"));
  }
}

class TestOfFileUpload extends UnitTestCase {
  function test_files_array() {
    $files = array(
      'userfile' => array(
        'name' => 'test.pdf',
        'tmp_name' => 'tmp85937457',
        'size' => '1024',
        'type' => 'application/pdf',
        'error' => 0,
      )
    );
    $glob = new k_adapter_MockGlobalsAccess(array(), array(), array('SERVER_NAME' => 'localhost', 'SCRIPT_NAME' => '', 'REQUEST_URI' => ''), array(), array(), $files);
    $http = new k_HttpRequest(null, null, new k_DefaultIdentityLoader(), null, null, $glob);
    $files = $http->file();
    $this->assertTrue(is_array($files));
    $file = $http->file('userfile');
    $this->assertEqual($file->name(), 'test.pdf');
    $this->assertEqual($file->type(), 'application/pdf');
    $this->assertEqual($file->size(), '1024');
    $this->assertEqual($file->key(), 'userfile');
  }
  function test_normalizing_files_array_when_multiple_files() {
    $files = array(
      'userfile' => array(
        'name' => array(
          'test.pdf',
          'test.doc',
        ),
        'tmp_name' => array(
          'tmp85937457',
          'tmp45937457',
        ),
        'size' => array(
          '1024',
          '2048',
        ),
        'type' => array(
          'application/pdf',
          'application/ms-word',
        ),
        'error' => array(
          0,
          0,
        )
      )
    );
    $expected = array(
      'userfile' => array(
        array(
          'name' => 'test.pdf',
          'tmp_name' => 'tmp85937457',
          'size' => '1024',
          'type' => 'application/pdf',
          'error' => 0,
        ),
        array(
          'name' => 'test.doc',
          'tmp_name' => 'tmp45937457',
          'size' => '2048',
          'type' => 'application/ms-word',
          'error' => 0,
        )
      )
    );
    $g = new k_adapter_SafeGlobalsAccess(new k_charset_Latin1CharsetStrategy(), true);
    $normalized = $g->normalizeFiles($files);
    $this->assertEqual($expected, $normalized);
  }
  function test_saving_an_uploaded_file() {
    $files = array(
      'userfile' => array(
        'name' => 'test.pdf',
        'tmp_name' => 'tmp85937457',
        'size' => '1024',
        'type' => 'application/pdf',
        'error' => 0,
      )
    );
    $glob = new k_adapter_MockGlobalsAccess(array(), array(), array('SERVER_NAME' => 'localhost', 'SCRIPT_NAME' => '', 'REQUEST_URI' => ''), array(), array(), $files);
    $file_access = new k_adapter_MockUploadedFileAccess();
    $http = new k_HttpRequest(null, null, new k_DefaultIdentityLoader(), null, null, $glob, null, null, $file_access);
    $http->file('userfile')->writeTo('/dev/null');
    $this->assertEqual(array(array('tmp85937457', '/dev/null')), $file_access->actions);
  }
}

class TestOfDispatching extends UnitTestCase {

  function test_root_gives_request_uri_as_subspace() {
    $glob = new k_adapter_MockGlobalsAccess(array(), array(), array('SERVER_NAME' => 'localhost'));
    $http = new k_HttpRequest('', '/foo/bar', new k_DefaultIdentityLoader(), null, null, $glob);
    $this->assertEqual($http->subspace(), "/foo/bar");
  }

  function test_first_component_has_root_subspace() {
    $glob = new k_adapter_MockGlobalsAccess(array(), array(), array('SERVER_NAME' => 'localhost'));
    $http = new k_HttpRequest('', '/foo/bar', new k_DefaultIdentityLoader(), null, null, $glob);
    $components = new k_DefaultComponentCreator();
    $root = $components->create('test_CircularComponent', $http);
    $this->assertEqual($root->subspace(), "foo/bar");
  }

  function test_dispatch() {
    $expected = "Dispatching:
  name: ''
  next: 'foo'
  subtype: ''
  url: '/web2.0/'
Dispatching:
  name: 'foo'
  next: 'bar'
  subtype: ''
  url: '/web2.0/foo'
Dispatching:
  name: 'bar'
  next: 'cux'
  subtype: ''
  url: '/web2.0/foo/bar'
Dispatching:
  name: 'cux'
  next: ''
  subtype: ''
  url: '/web2.0/foo/bar/cux'
Executing
";
    $glob = new k_adapter_MockGlobalsAccess(array(), array(), array('SERVER_NAME' => 'localhost'));
    $http = new k_HttpRequest('/web2.0', '/web2.0/foo/bar/cux', new k_DefaultIdentityLoader(), null, null, $glob);
    $components = new k_DefaultComponentCreator();
    $root = $components->create('test_CircularComponent', $http);
    $result = $root->dispatch();
    $this->assertEqual($result, $expected);

    $http = new k_HttpRequest('/web2.0', '/web2.0/foo/bar/cux/', new k_DefaultIdentityLoader(), null, null, $glob);
    $components = new k_DefaultComponentCreator();
    $root = $components->create('test_CircularComponent', $http);
    $result = $root->dispatch();
    $this->assertEqual($result, $expected);
  }

  function test_subtype_doesnt_affect_dispatch() {
    $expected = "Dispatching:
  name: ''
  next: 'foo'
  subtype: ''
  url: '/web2.0/'
Dispatching:
  name: 'foo'
  next: 'bar'
  subtype: 'ninja'
  url: '/web2.0/foo.ninja'
Dispatching:
  name: 'bar'
  next: ''
  subtype: ''
  url: '/web2.0/foo.ninja/bar'
Executing
";
    $glob = new k_adapter_MockGlobalsAccess(array(), array(), array('SERVER_NAME' => 'localhost'));
    $http = new k_HttpRequest('/web2.0', '/foo.ninja/bar', new k_DefaultIdentityLoader(), null, null, $glob);
    $components = new k_DefaultComponentCreator();
    $root = $components->create('test_CircularComponent', $http);
    $result = $root->dispatch();
    $this->assertEqual($expected, $result);
  }

}

class TestOfHttpRequest extends UnitTestCase {
  function createHttp($headers) {
    $glob = new k_adapter_MockGlobalsAccess(array(), array(), array('SERVER_NAME' => 'example.org'), $headers);
    return new k_HttpRequest('', '', new k_DefaultIdentityLoader(), null, null, $glob);
  }

  function test_negotiate_returns_first_match_from_accept_header() {
    $request = $this->createHttp(array('accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'));
    $this->assertEqual($request->negotiateContentType(array('text/html','application/xhtml+xml','application/xml')), 'text/html');
  }

  function test_negotiate_returns_match_with_highest_rank_from_accept_header() {
    $request = $this->createHttp(array('accept' => 'text/html;q=0.1,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'));
    $this->assertEqual($request->negotiateContentType(array('text/html','application/xhtml+xml','application/xml')), 'application/xhtml+xml');
  }

  function test_negotiate_returns_candidate_with_exact_match() {
    $request = $this->createHttp(array('accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'));
    $this->assertEqual($request->negotiateContentType(array('application/xml')), 'application/xml');
  }

  function test_negotiate_returns_candidate_with_partial_match() {
    $request = $this->createHttp(array('accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'));
    $this->assertEqual($request->negotiateContentType(array('application/*')), 'application/*');
  }

  function test_negotiate_returns_first_candidate_on_no_header() {
    $request = $this->createHttp(array('accept' => ''));
    $this->assertEqual($request->negotiateContentType(array('text/html')), 'text/html');
  }

  function test_user_override_overrides_accept_header() {
    $request = $this->createHttp(array('accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'));
    $this->assertEqual($request->negotiateContentType(array('text/html', 'foo/bar'), 'foo/bar'), 'foo/bar');
  }

  function test_negotiate_simple_subtype_interpreted_as_major_type() {
    $request = $this->createHttp(array('accept' => 'text/html,application/xhtml+xml,application/xml;q=0.9,*/*;q=0.8'));
    $this->assertEqual($request->negotiateContentType(array('text/html', 'foo'), 'foo'), 'foo');
  }

  function test_negotiate_internet_explorer_headerline() {
    $request = $this->createHttp(array('accept' => 'image/gif, image/x-xbitmap, image/jpeg, image/pjpeg, application/x-shockwave-flash, application/xaml+xml, application/vnd.ms-xpsdocument, application/x-ms-xbap, application/x-ms-application, application/vnd.ms-excel, application/vnd.ms-powerpoint, application/msword, */*'));
    $this->assertEqual($request->negotiateContentType(array('text/html')), 'text/html');
  }
}

class TestOfHttpResponse extends UnitTestCase {
  function test_404_response_should_output_custom_content_if_any() {
    $output = new k_adapter_DummyOutputAccess();
    $response = new k_HtmlResponse("I didn't find it");
    $response->setStatus(404);
    $response->out($output);
    $this->assertEqual(404, $output->http_response_code);
    $this->assertEqual("I didn't find it", $output->body);
  }
}

class TestOfContentTypeDispathing extends UnitTestCase {
  function createComponent($method, $headers = array()) {
    $glob = new k_adapter_MockGlobalsAccess(array(), array(), array('SERVER_NAME' => 'localhost', 'REQUEST_METHOD' => $method), $headers);
    $http = new k_HttpRequest('', '/', new k_DefaultIdentityLoader(), null, null, $glob);
    $components = new k_DefaultComponentCreator();
    return $components->create('test_ContentTypeComponent', $http);
  }
  function test_posting_with_a_content_type_calls_specific_handler() {
    $root = $this->createComponent('post', array('content-type' => 'application/json'));
    $this->assertEqual("postJson called", $root->dispatch());
  }
  function test_post_multipart() {
    $root = $this->createComponent('post', array('content-type' => 'multipart/form-data; boundary=---------------------------1991290281749441721928095653'));
    $this->assertEqual("postMultipart called", $root->dispatch());
  }
  function test_posting_without_a_content_type_fails_with_not_acceptable_when_there_is_at_least_one_candidate() {
    $root = $this->createComponent('post');
    try {
      $root->dispatch();
      $this->fail("Expected exception not caught");
    } catch (k_NotAcceptable $ex) {
      $this->pass();
    }
  }
  function test_putting_without_a_content_type_fails_with_not_implemented_when_there_are_no_candidates() {
    $root = $this->createComponent('put');
    try {
      $root->dispatch();
      $this->fail("Expected exception not caught");
    } catch (k_NotImplemented $ex) {
      $this->pass();
    }
  }
  function test_a_content_type_with_explicit_charset_utf8_is_treated_transparently() {
    $root = $this->createComponent('post', array('content-type' => 'application/json; charset=utf-8'));
    $this->assertEqual("postJson called", $root->dispatch());
  }
  function test_a_content_type_with_explicit_charset_other_than_utf8_raises_an_error() {
    $root = $this->createComponent('post', array('content-type' => 'application/json; charset=iso-8859-1'));
    try {
      $root->dispatch();
      $this->fail("Expected exception");
    } catch (k_UnsupportedContentTypeCharsetException $ex) {
      $this->pass("Exception caught");
    }
  }
}

class TestOfLanguageLoading {
  function createComponent($method, $headers = array()) {
    $glob = new k_adapter_MockGlobalsAccess(array(), array(), array('SERVER_NAME' => 'localhost', 'REQUEST_METHOD' => $method), $headers);
    $language_loader = new test_LanguageLoader();
    $http = new k_HttpRequest('', '/', new k_DefaultIdentityLoader(), $language_loader, null, $glob);
    $components = new k_DefaultComponentCreator();
    return $components->create('test_ContentTypeComponent', $http);
  }
  function test_that_a_language_is_loaded() {
    $component = $this->createComponent('get');
    $language = $component->language();
    $this->assertTrue($language instanceof k_Language);
  }
}

class TestOfTranslatorLoading {
  function createComponent($method, $headers = array()) {
    $glob = new k_adapter_MockGlobalsAccess(array(), array(), array('SERVER_NAME' => 'localhost', 'REQUEST_METHOD' => $method), $headers);
    $translator_loader = new test_TranslatorLoader();
    $http = new k_HttpRequest('', '/', new k_DefaultIdentityLoader(), null, $translator_loader, $glob);
    $components = new k_DefaultComponentCreator();
    return $components->create('test_ContentTypeComponent', $http);
  }
  function test_that_a_translator_is_loaded() {
    $component = $this->createComponent('get');
    $translator = $component->language();
    $this->assertTrue($translator instanceof k_Translator);
  }
}