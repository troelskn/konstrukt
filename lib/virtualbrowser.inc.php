<?php
require_once 'k.inc.php';
require_once 'simpletest/web_tester.php';

class k_VirtualSimpleBrowser extends SimpleBrowser {
  protected $root_class_name;
  protected $components;
  protected $charset_strategy;
  /**
    * @param string
    * @param ???
    * @param ???
    * @return null
    */
  function __construct($root_class_name, k_ComponentCreator $components = null, k_charset_CharsetStrategy $charset_strategy = null) {
    $this->root_class_name = $root_class_name;
    $this->components = $components;
    $this->charset_strategy = $charset_strategy;
    parent::__construct();
  }
  /**
    * @return k_VirtualSimpleUserAgent
    */
  protected function createUserAgent() {
    return new k_VirtualSimpleUserAgent($this->root_class_name, $this->components, $this->charset_strategy);
  }
}

class k_SimpleOutputAccess implements k_adapter_OutputAccess {
  protected $headers = array();
  protected $content = "";
  /**
    * @param string
    * @param bool
    * @param long
    * @return null
    */
  function header($string, $replace = true, $http_response_code = null) {
    $this->headers[] = $string;
  }
  /**
    * @param string
    * @return null
    */
  function write($bytes) {
    $this->content .= $bytes;
  }
  /**
    * @return null
    */
  function endSession() {}
  /**
    * @param SimpleUrl
    * @param array
    * @param string
    * @return k_ActingAsSimpleHttpResponse
    */
  function toSimpleHttpResponse($url, $data = array(), $method = 'GET') {
    return new k_ActingAsSimpleHttpResponse($url, $data, $this->headers, $this->content, $method);
  }
}

class k_ActingAsSimpleHttpResponse {
  protected $url;
  protected $method = "GET";
  protected $encoding;
  public $headers;
  public $content;
  /**
    * @param SimpleUrl
    * @param array
    * @param array
    * @param string
    * @param string
    * @return null
    */
  function __construct($url, $data, $headers, $content, $method) {
    $this->url = $url;
    $this->headers = new SimpleHttpHeaders(implode("\r\n", $headers));
    $this->content = $content;
    if ($method == 'POST') {
      $this->encoding =  new SimplePostEncoding($data);
    } else {
      $url_query = array();
      parse_str(str_replace('?', '', $url->getEncodedRequest()), $url_query);
      if ($method == 'HEAD') {
        $this->encoding =  new SimpleHeadEncoding($url_query);
      } else {
        $this->encoding =  new SimpleGetEncoding($url_query);
      }
    }
  }
  /**
    * @return boolean
    */
  function isError() {
    return false;
  }
  /**
    * @return boolean
    */
  function getError() {
    return false;
  }
  /**
    * @return string
    */
  function getMethod() {
    return $this->method;
  }
  /**
    * @return SimpleUrl
    */
  function getUrl() {
    return $this->url;
  }
  /**
    * @return SimpleGetEncoding
    */
  function getRequestData() {
    return $this->encoding;
  }
  /**
    * @return string
    */
  function getSent() {
    return 'Request Not Available';
  }
  /**
    * @return SimpleHttpHeaders
    */
  function getHeaders() {
    return $this->headers;
  }
  /**
    * @return string
    */
  function getContent() {
    return $this->content;
  }
}

class k_VirtualSimpleUserAgent {
  protected $cookie_access;
  protected $session_access;
  protected $charset_strategy;
  protected $components;
  protected $root_class_name;
  protected $servername;
  protected $max_redirects = 3;
  /**
    * @param string
    * @param ???
    * @param ???
    * @return null
    */
  function __construct($root_class_name, k_ComponentCreator $components = null, k_charset_CharsetStrategy $charset_strategy = null) {
    $this->cookie_access = new k_adapter_MockCookieAccess('', array());
    $this->session_access = new k_adapter_MockSessionAccess($this->cookie_access);
    $this->components = $components ? $components : new k_DefaultComponentCreator();
    $this->charset_strategy = $charset_strategy ? $charset_strategy : new k_charset_Utf8CharsetStrategy();
    $this->root_class_name = $root_class_name;
    if (!$root_class_name) {
      throw new Exception("root_class_name not specified");
    }
    $this->servername = 'localhost';
  }
  function restart($date = false) {
    $this->cookie_access = new k_adapter_MockCookieAccess('', array());
    $this->session_access = new k_adapter_MockSessionAccess($this->cookie_access);
  }
  /**
    * @param bool
    * @param bool
    * @param bool
    * @return null
    */
  function useProxy($proxy, $username, $password) {}
  /**
    * @param long
    * @return boolean
    */
  protected function isTooManyRedirects($redirects) {
    return ($redirects > $this->max_redirects);
  }
  /**
    * @param SimpleUrl
    * @param SimpleEncoding
    * @return k_ActingAsSimpleHttpResponse
    */
  protected function fetch(SimpleUrl $url, SimpleEncoding $parameters) {
    // extract primitives from SimpleTest abstractions
    $url_path = $url->getPath();
    $url_query = array();
    parse_str(str_replace('?', '', $url->getEncodedRequest()), $url_query);
    $method = $parameters->getMethod();
    $data = array();
    foreach ($parameters->getAll() as $pair) {
      $data[$pair->getKey()] = $pair->getValue();
    }
    if (!in_array($url->getHost(), array("", $this->servername))) {
      return new k_ActingAsSimpleHttpResponse($url, $data, array("HTTP/1.1 502"), "External URL requested: " . $url->asString(), $method);
    }
    // set up a mocked environment
    $server = array(
      'SERVER_NAME' => $this->servername,
      'REQUEST_METHOD' => $method,
      'REQUEST_URI' => $url_path);
    $superglobals = new k_adapter_MockGlobalsAccess($url_query, $data, $server);
    $response = k()
      ->setContext(new k_HttpRequest("", null, new k_DefaultIdentityLoader(), $superglobals, $this->cookie_access, $this->session_access))
      ->setComponentCreator($this->components)
      ->setCharsetStrategy($this->charset_strategy)
      ->run($this->root_class_name);
    $output = new k_SimpleOutputAccess();
    $response->out($output);
    return $output->toSimpleHttpResponse($url, $data, $method);
  }
  /**
    * @param SimpleUrl
    * @param SimpleEncoding
    * @return k_ActingAsSimpleHttpResponse
    */
  function fetchResponse($url, $encoding) {
    if ($encoding->getMethod() != 'POST') {
      $url->addRequestParameters($encoding);
      $encoding->clear();
    }
    $response = $this->fetchWhileRedirected($url, $encoding);
    if ($headers = $response->getHeaders()) {
      if ($headers->isChallenge()) {
        $this->authenticator->addRealm(
          $url,
          $headers->getAuthentication(),
          $headers->getRealm());
      }
    }
    return $response;
  }
  /**
    * @param SimpleUrl
    * @param SimpleEncoding
    * @return k_ActingAsSimpleHttpResponse
    */
  protected function fetchWhileRedirected($url, $encoding) {
    $redirects = 0;
    do {
      $response = $this->fetch($url, $encoding);
      if ($response->isError()) {
        return $response;
      }
      $headers = $response->getHeaders();
      $location = new SimpleUrl($headers->getLocation());
      $url = $location->makeAbsolute($url);
      // if ($this->cookies_enabled) {
      //   $headers->writeCookiesToJar($this->cookie_jar, $url);
      // }
      if (! $headers->isRedirect()) {
        break;
      }
      $encoding = new SimpleGetEncoding();
    } while (! $this->isTooManyRedirects(++$redirects));
    return $response;
  }
}

