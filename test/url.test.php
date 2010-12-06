<?php
error_reporting(E_ALL | E_STRICT);
set_include_path(dirname(__FILE__) . PATH_SEPARATOR . dirname(__FILE__) . '/../lib/' . PATH_SEPARATOR . get_include_path());

// You need to have simpletest in your include_path
require_once 'simpletest/unit_tester.php';
if (realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__) {
  require_once 'simpletest/autorun.php';
}

require_once '../lib/konstrukt/konstrukt.inc.php';
require_once 'support/mocks.inc.php';

class TestOfUrlGeneration extends UnitTestCase {

  function createHttp($href_base, $request_uri) {
    $glob = new k_adapter_MockGlobalsAccess(array(), array(), array('SERVER_NAME' => 'example.org'));
    return new k_HttpRequest($href_base, $request_uri, new k_DefaultIdentityLoader(), null, null, $glob);
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
    $http = new k_HttpRequest(null, null, new k_DefaultIdentityLoader(), null, null, $glob);
    $this->assertEqual('/', $http->url());
  }

  function test_when_specifying_a_subtype_it_replaces_the_current() {
    $http = $this->createHttp('/foo', '/foo/bar.text');
    $components = new k_DefaultComponentCreator();
    $root = $components->create('test_CircularComponent', $http);
    $first = $components->create('test_CircularComponent', $root);
    $this->assertEqual($first->url(".xml"), "/foo/bar.xml");
  }

  function test_an_subtype_is_removed() {
    $http = $this->createHttp('/foo', '/foo/bar.text');
    $components = new k_DefaultComponentCreator();
    $root = $components->create('test_CircularComponent', $http);
    $first = $components->create('test_CircularComponent', $root);
    $this->assertEqual("/foo/bar", $first->url("."));
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
    $this->assertEqual($http->url(array("banjo.html")), "banjo.html");
  }

  function test_unidentified_bug_with_url_builder() {
    $http = $this->createHttp('', '');
    $components = new k_DefaultComponentCreator();
    $url = 'http://www.example.org/';
    $this->assertEqual($http->url('show', array('event_id' => $url, 'create')), 'show?create&event_id=http%3A%2F%2Fwww.example.org%2F');
  }

  function test_subview_cares_about_underscores() {
    $http = $this->createHttp('/web2.0', '/foo/bar?delete');
    $components = new k_DefaultComponentCreator();
    $root = $components->create('test_CircularComponent', $http);
    $this->assertEqual('delete', $root->subview());

    $http = $this->createHttp('/web2.0', '/foo/bar?delete_something');
    $components = new k_DefaultComponentCreator();
    $root = $components->create('test_CircularComponent', $http);
    $this->assertEqual('delete_something', $root->subview());
  }
}
