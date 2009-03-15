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
    $http = new k_HttpRequest(null, null, new k_DefaultIdentityLoader(), $glob);
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
    $http = new k_HttpRequest(null, null, new k_DefaultIdentityLoader(), $glob, null, null, $file_access);
    $http->file('userfile')->writeTo('/dev/null');
    $this->assertEqual(array(array('tmp85937457', '/dev/null')), $file_access->actions);
  }
}

class TestOfDispatching extends UnitTestCase {

  function test_root_gives_request_uri_as_subspace() {
    $glob = new k_adapter_MockGlobalsAccess(array(), array(), array('SERVER_NAME' => 'localhost'));
    $http = new k_HttpRequest('', '/foo/bar', new k_DefaultIdentityLoader(), $glob);
    $this->assertEqual($http->subspace(), "/foo/bar");
  }

  function test_first_component_has_root_subspace() {
    $glob = new k_adapter_MockGlobalsAccess(array(), array(), array('SERVER_NAME' => 'localhost'));
    $http = new k_HttpRequest('', '/foo/bar', new k_DefaultIdentityLoader(), $glob);
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
    $http = new k_HttpRequest('/web2.0', '/web2.0/foo/bar/cux', new k_DefaultIdentityLoader(), $glob);
    $components = new k_DefaultComponentCreator();
    $root = $components->create('test_CircularComponent', $http);
    $result = $root->dispatch();
    $this->assertEqual($result, $expected);

    $http = new k_HttpRequest('/web2.0', '/web2.0/foo/bar/cux/', new k_DefaultIdentityLoader(), $glob);
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
  url: '/web2.0/foo;ninja'
Dispatching:
  name: 'bar'
  next: ''
  subtype: ''
  url: '/web2.0/foo;ninja/bar'
Executing
";
    $glob = new k_adapter_MockGlobalsAccess(array(), array(), array('SERVER_NAME' => 'localhost'));
    $http = new k_HttpRequest('/web2.0', '/foo;ninja/bar', new k_DefaultIdentityLoader(), $glob);
    $components = new k_DefaultComponentCreator();
    $root = $components->create('test_CircularComponent', $http);
    $result = $root->dispatch();
    $this->assertEqual($result, $expected);
  }

}

class TestOfUrlGeneration extends UnitTestCase {

  function createHttp($href_base, $request_uri) {
    $glob = new k_adapter_MockGlobalsAccess(array(), array(), array('SERVER_NAME' => 'example.org'));
    return new k_HttpRequest($href_base, $request_uri, new k_DefaultIdentityLoader(), $glob);
  }

  function test_url_state_param_propagates_over_url() {
    $http = $this->createHttp('', '/foo/bar');
    $components = new k_DefaultComponentCreator();
    $root = $components->create('test_ExposedComponent', $http);
    $root->getUrlState()->set('foo', 'bar');
    $this->assertEqual($root->url('', array('zip' => 'zap')), "/?foo=bar&zip=zap");
  }

  function test_getting_unset_stateful_parameter_defaults_to_null() {
    $http = $this->createHttp('', '/foo/bar');
    $components = new k_DefaultComponentCreator();
    $root = $components->create('test_ExposedComponent', $http);
    $this->assertNull($root->getUrlState()->get('foo'));
  }

  function test_url_state_param_set_to_default_value_doesnt_propagate() {
    $http = $this->createHttp('', '/foo/bar');
    $components = new k_DefaultComponentCreator();
    $root = $components->create('test_ExposedComponent', $http);
    $root->getUrlState()->init('foo', 'bar');
    $root->getUrlState()->set('foo', 'bar');
    $this->assertEqual($root->url('', array('zip' => 'zap')), "/?zip=zap");
  }

  function test_create_href() {
    $http = $this->createHttp('', '/foo/bar');
    $components = new k_DefaultComponentCreator();
    $root = $components->create('test_CircularComponent', $http);
    $first = $components->create('test_CircularComponent', $root);
    $second = $components->create('test_CircularComponent', $first);
    $this->assertEqual($second->url(), "/foo/bar");
    $this->assertEqual($second->url(".."), "/foo");
    $this->assertEqual($second->url("/ding"), "/ding");
  }

  function test_root_must_end_with_trailing_slash_but_subcomponents_mustnt() {
    /*
     * This probably deserves some explanation.
     * Basically, there are two schools of thought, in regards to URI paths
     * One sees paths as filesystem paths, and it therefore makes sense to display
     * directories with trailing slash, but files without.
     * The other perspective is that paths could address any kind of hierarchical
     * structure, where there isn't any distinction between node types. In such a
     * system, a node should never be addressed with a trailing slash - Except
     * for the root-node.
     */
    $http = $this->createHttp('/foo', '/foo/bar');
    $components = new k_DefaultComponentCreator();
    $root = $components->create('test_CircularComponent', $http);
    $first = $components->create('test_CircularComponent', $root);
    $this->assertEqual($root->url(), "/foo/");
    $this->assertEqual($first->url(), "/foo/bar");
  }

  // Bug reported by Ander Ekdahl
  function test_when_script_name_is_slash_href_base_should_be_slash() {
    $glob = new k_adapter_MockGlobalsAccess(array(), array(), array('SERVER_NAME' => 'example.org', 'SCRIPT_NAME' => '/', 'REQUEST_URI' => '/'));
    $http = new k_HttpRequest(null, null, new k_DefaultIdentityLoader(), $glob);
    $this->assertEqual('/', $http->url());
  }

  function test_when_specifying_a_subtype_it_replaces_the_current() {
    $http = $this->createHttp('/foo', '/foo/bar;text');
    $components = new k_DefaultComponentCreator();
    $root = $components->create('test_CircularComponent', $http);
    $first = $components->create('test_CircularComponent', $root);
    $this->assertEqual($first->url(";xml"), "/foo/bar;xml");
  }

  function test_an_subtype_is_removed() {
    $http = $this->createHttp('/foo', '/foo/bar;text');
    $components = new k_DefaultComponentCreator();
    $root = $components->create('test_CircularComponent', $http);
    $first = $components->create('test_CircularComponent', $root);
    $this->assertEqual($first->url(";"), "/foo/bar");
  }

  function test_passing_array_as_first_argument_joins_segments() {
    $http = $this->createHttp('', '');
    $components = new k_DefaultComponentCreator();
    $this->assertEqual($http->url(array('foo', 'bar')), "foo/bar");
  }

  function test_passing_array_as_first_argument_encodes_segments() {
    $http = $this->createHttp('', '');
    $components = new k_DefaultComponentCreator();
    $this->assertEqual($http->url(array("bl\xC3\xA5b\xC3\xA6rgr\xC3\xB8d", 'bar')), "bl%C3%A5b%C3%A6rgr%C3%B8d/bar");
  }

  function test_passing_array_as_first_argument_doesnt_encode_semicolons() {
    $http = $this->createHttp('', '');
    $components = new k_DefaultComponentCreator();
    $this->assertEqual($http->url(array("banjo;html")), "banjo;html");
  }

}

class TestOfHttpRequest extends UnitTestCase {
  function createHttp($headers) {
    $glob = new k_adapter_MockGlobalsAccess(array(), array(), array('SERVER_NAME' => 'example.org'), $headers);
    return new k_HttpRequest('', '', new k_DefaultIdentityLoader(), $glob);
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
    $response = new k_HttpResponse(404, "I didn't find it");
    $response->out($output);
    $this->assertEqual(404, $output->http_response_code);
    $this->assertEqual("I didn't find it", $output->body);
  }
}
