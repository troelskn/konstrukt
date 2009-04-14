<?php
require_once 'konstrukt/adapter.inc.php';
require_once 'konstrukt/charset.inc.php';
require_once 'konstrukt/response.inc.php';
require_once 'konstrukt/logging.inc.php';

/**
 * A factory for creating components on-the-fly.
 * A ComponentCreator can create new instances of k_Component.
 * If you use a DI container, you should write an adapter that implements this interface
 */
interface k_ComponentCreator {
  /**
    * Sets the debugger to use.
    * @param k_DebugListener
    * @return void
    */
  function setDebugger(k_DebugListener $debugger);
  /**
    * Creates a new instance of the requested class.
    * @param string
    * @param k_Context
    * @param string
    * @return k_Component
    */
  function create($class_name, k_Context $context, $namespace = "");
}

/**
 * A simple implementation, which doesn't handle any dependencies.
 * You can use this, for very simple applications or if you prefer a global
 * mechanism for dependencies (Such as Singleton).
 */
class k_DefaultComponentCreator implements k_ComponentCreator {
  /** @var k_DebugListener */
  protected $debugger;
  /** @var k_Document */
  protected $document;
  /** @var array */
  protected $aliases = array();
  /**
    * @param k_Document
    * @return void
    */
  function __construct($document = null) {
    $this->document = $document ? $document : new k_Document();
    $this->setDebugger(new k_MultiDebugListener());
  }
  /**
    * Sets the debugger to use.
    * @param k_DebugListener
    * @return void
    */
  function setDebugger(k_DebugListener $debugger) {
    $this->debugger = $debugger;
  }
  /**
    * When trying to instantiate $class_name, instead use $implementing_class
    */
  function setImplementation($class_name, $implementing_class) {
    $this->aliases[strtolower($class_name)] = $implementing_class;
  }
  /**
    * Creates a new instance of the requested class.
    * @param string
    * @param k_Context
    * @param string
    * @return k_Component
    */
  function create($class_name, k_Context $context, $namespace = "") {
    $component = $this->instantiate(isset($this->aliases[strtolower($class_name)]) ? $this->aliases[strtolower($class_name)] : $class_name);
    $component->setContext($context);
    $component->setUrlState(new k_UrlState($context, $namespace));
    $component->setDocument($this->document);
    $component->setComponentCreator($this);
    $component->setDebugger($this->debugger);
    return $component;
  }
  /**
    * @param string
    * @return k_Component
    */
  protected function instantiate($class_name) {
    return new $class_name();
  }
}

/**
 * A debuglistener is an object that can receive various events and send
 * them to some place for further inspection.
 */
interface k_DebugListener {
  /**
    * @param k_Context
    * @return void
    */
  function logRequestStart(k_Context $context);
  /**
    * @param Exception
    * @return void
    */
  function logException(Exception $ex);
  /**
    * @param k_Component
    * @param string
    * @param string
    * @return void
    */
  function logDispatch($component, $name, $next);
  /**
    * @param mixed
    * @return void
    */
  function log($mixed);
  /**
    * Allows the debugger to change the k_Response before output.
    * This is used to inject a debugbar into the response. It is perhaps not the most elegant solution, but it works.
    * @param k_Response
    * @return k_Response
    */
  function decorate(k_Response $response);
}

/**
 * A dummy implementation of k_DebugListener, which does nothing.
 */
class k_VoidDebugListener implements k_DebugListener {
  function logRequestStart(k_Context $context) {}
  function logException(Exception $ex) {}
  function logDispatch($component, $name, $next) {}
  function log($mixed) {}
  function decorate(k_Response $response) {
    return $response;
  }
}

/**
 * Decorator that allowsmultiple k_DebugListener objects to receive the same events.
 */
class k_MultiDebugListener implements k_DebugListener {
  protected $listeners = array();
  function add(k_DebugListener $listener) {
    $this->listeners[] = $listener;
  }
  function logRequestStart(k_Context $context) {
    foreach ($this->listeners as $listener) {
      $listener->logRequestStart($context);
    }
  }
  function logException(Exception $ex) {
    foreach ($this->listeners as $listener) {
      $listener->logException($ex);
    }
  }
  /**
    * @param k_Component
    * @param string
    * @param string
    * @return void
    */
  function logDispatch($component, $name, $next) {
    foreach ($this->listeners as $listener) {
      $listener->logDispatch($component, $name, $next);
    }
  }
  function log($mixed) {
    foreach ($this->listeners as $listener) {
      $listener->log($mixed);
    }
  }
  /**
    * @param k_Response
    * @return k_Response
    */
  function decorate(k_Response $response) {
    foreach ($this->listeners as $listener) {
      $response = $listener->decorate($response);
    }
    return $response;
  }
}

/**
 * For registry-style component wiring.
 * You can use this for legacy applications, that are tied to k_Registry.
 * usage:
 *
 *     $registry = new k_Registry();
 *     k_run('Root', new k_HttpRequest(), new k_RegistryComponentCreator($registry))->out();
 */
class k_RegistryComponentCreator extends k_DefaultComponentCreator {
  protected $registry;
  function __construct($registry) {
    parent::__construct();
    $this->registry = $registry;
  }
  protected function instantiate($class_name) {
    return new $class_name($this->registry);
  }
}

/**
 * Adapter for using the Phemto dependency injection container for creating components.
 * This relies on the current (under development) implementation of Phemto, available from:
 * https://phemto.svn.sourceforge.net/svnroot/phemto/trunk
 */
class k_PhemtoAdapter extends k_DefaultComponentCreator {
  /** @var Phemto */
  protected $injector;
  /**
    * @param Phemto
    * @return void
    */
  function __construct(Phemto $injector, $document = null) {
    parent::__construct($document);
    $this->injector = $injector;
  }
  /**
    * @param string
    * @return k_Component
    */
  protected function instantiate($class_name) {
    return $this->injector->create($class_name);
  }
}

/**
 * Representation of the applications' user
 */
interface k_Identity {
  /**
   * Return the username
   */
  function user();
  /**
   * Return true if the user is anonymous (not authenticated)
   */
  function anonymous();
}

/**
 * A factory for recognising and loading the users identity
 */
interface k_IdentityLoader {
  /**
   * Using the context as input, the identityloader should find and return a userobject.
   * @return k_Identity
   */
  function load(k_Context $context);
}

/**
 * A default implementation, which always returns k_Anonymous
 */
class k_DefaultIdentityLoader implements k_IdentityLoader {
  function load(k_Context $context) {
    return new k_Anonymous();
  }
}

/**
 * Baseclass for basic http authentication.
 */
class k_BasicHttpIdentityLoader implements k_IdentityLoader {
  function load(k_Context $context) {
    if (preg_match('/^Basic (.*)$/', $context->header('authorization'), $mm)) {
      list($username, $password) = explode(':', base64_decode($mm[1]), 2);
      $user = $this->selectUser($username, $password);
      if ($user) {
        return $user;
      }
    }
    return new k_Anonymous();
  }
  /**
   * This should be overridden in subclass to select user from eg. a database or similar.
   */
  function selectUser($username, $password) {
    return new k_AuthenticatedUser($username);
  }
}

/**
 * Default implementation of k_Identity ... doesn't do much
 */
class k_Anonymous implements k_Identity {
  function user() {
    return "";
  }
  function anonymous() {
    return true;
  }
}

/**
 * Basic implementation of k_Identity, used for authenticated users.
 */
class k_AuthenticatedUser implements k_Identity {
  protected $user;
  function __construct($user) {
    $this->user = $user;
  }
  function user() {
    return $this->user;
  }
  function anonymous() {
    return false;
  }
}

/**
 * A context provides a full encapsulation of global variables, for any sub-components
 * The most important direct implementations are k_HttpRequest and k_Component
 */
interface k_Context {
  function query($key = null, $default = null);
  function body($key = null, $default = null);
  function header($key = null, $default = null);
  function cookie($key = null, $default = null);
  function session($key = null, $default = null);
  function file($key = null, $default = null);
  function rawHttpRequestBody();
  function requestUri();
  function method();
  function serverName();
  function identity();
  function url($path = "", $params = array());
  function subspace();
  function negotiateContentType($candidates = array());
}

/**
 * Usually the top-level context ... provides access to the http-protocol
 */
class k_HttpRequest implements k_Context {
  /** @var string */
  protected $href_base;
  /** @var string */
  protected $subspace;
  /** @var array */
  protected $query;
  /** @var array */
  protected $body;
  /** @var string */
  protected $rawHttpRequestBody;
  /** @var string */
  protected $request_uri;
  /** @var array */
  protected $server;
  /** @var array */
  protected $files;
  /** @var array */
  protected $headers;
  /** @var k_adapter_CookieAccess */
  protected $cookie_access;
  /** @var k_adapter_SessionAccess */
  protected $session_access;
  /** @var k_IdentityLoader */
  protected $identity_loader;
  /** @var k_Identity */
  protected $identity;
  /** @var k_ContentTypeNegotiator */
  protected $content_type_negotiator;
  /**
    * @param string
    * @param string
    * @param k_DefaultIdentityLoader
    * @param k_adapter_MockGlobalsAccess
    * @param k_adapter_MockCookieAccess
    * @param k_adapter_MockSessionAccess
    */
  function __construct($href_base = null, $request_uri = null, k_IdentityLoader $identity_loader = null, k_adapter_GlobalsAccess $superglobals = null, k_adapter_CookieAccess $cookie_access = null, k_adapter_SessionAccess $session_access = null, k_adapter_UploadedFileAccess $file_access = null) {
    if (preg_match('~/$~', $href_base)) {
      throw new Exception("href_base may _not_ have trailing slash");
    }
    if (preg_match('~^\w+://\w+\.~', $href_base)) {
      throw new Exception("href_base may _not_ include hostname");
    }
    if (!$superglobals) {
      $superglobals = new k_adapter_SafeGlobalsAccess(new k_charset_Utf8CharsetStrategy());
    }
    if (!$file_access) {
      $file_access = new k_adapter_DefaultUploadedFileAccess();
    }
    $this->query = $superglobals->query();
    $this->body = $superglobals->body();
    $this->rawHttpRequestBody = $superglobals->rawHttpRequestBody();
    $this->server = $superglobals->server();
    $this->files = array();
    foreach ($superglobals->files() as $key => $file_info) {
      $this->files[$key] = new k_adapter_UploadedFile($file_info, $key, $file_access);
    }
    $this->headers = $this->lowerKeys($superglobals->headers());
    $this->cookie_access = $cookie_access ? $cookie_access : new k_adapter_DefaultCookieAccess($this->server['SERVER_NAME'], $superglobals->cookie());
    $this->session_access = $session_access ? $session_access : new k_adapter_DefaultSessionAccess($this->cookie_access);
    $this->identity_loader = $identity_loader ? $identity_loader : new k_DefaultIdentityLoader();
    $this->href_base = $href_base === null ? preg_replace('~(.*)/.*~', '$1', $this->server['SCRIPT_NAME']) : $href_base;
    $this->request_uri = $request_uri === null ? $this->server['REQUEST_URI'] : $request_uri;
    $this->subspace =
      preg_replace(  // remove root
        '~^' . preg_quote($this->href_base, '~') . '~', '',
        preg_replace( // remove trailing query-string
          '~([?]{1}.*)$~', '',
          $this->request_uri));
    $this->content_type_negotiator = new k_ContentTypeNegotiator($this->header('accept'));
  }
  /**
    * @param array
    * @return array
    */
  protected function lowerKeys($input) {
    $output = array();
    foreach ($input as $key => $value) {
      $output[strtolower($key)] = $value;
    }
    return $output;
  }
  /**
    * @param string
    * @param string
    * @return string
    */
  function query($key = null, $default = null) {
    return $key
      ? isset($this->query[$key])
        ? $this->query[$key]
        : $default
      : $this->query;
  }
  /**
    * @param string
    * @param string
    * @return string
    */
  function body($key = null, $default = null) {
    return $key
      ? isset($this->body[$key])
        ? $this->body[$key]
        : $default
      : $this->body;
  }
  /**
   * Returns the http-request body as-is. Note that you have to manually handle charset-issues for this.
   * @return string
   */
  function rawHttpRequestBody() {
    return $this->rawHttpRequestBody;
  }
  /**
    * @param string
    * @param mixed   The default value to return, if the value doesn't exist
    * @return string
    */
  function header($key = null, $default = null) {
    $key = strtolower($key);
    return $key
      ? isset($this->headers[$key])
        ? $this->headers[$key]
        : $default
      : $this->headers;
  }
  function cookie($key = null, $default = null) {
    return $key ? $this->cookie_access->get($key, $default) : $this->cookie_access->all();
  }
  /**
    * @param string
    * @param mixed   The default value to return, if the value doesn't exist
    * @return string
    */
  function session($key = null, $default = null) {
    return $key ? $this->session_access->get($key, $default) : $this->session_access;
  }
  function file($key = null, $default = null) {
    return $key
      ? isset($this->files[$key])
        ? $this->files[$key]
        : $default
      : $this->files;
  }
  /**
    * Gives back the HTTP-method
    * @return string
    */
  function method() {
    return strtolower($this->server['REQUEST_METHOD']);
  }
  function requestUri() {
    return $this->request_uri;
  }
  /**
    * Gives back the server name
    * @return string
    */
  function serverName() {
    return $this->server['SERVER_NAME'];
  }
  /**
    * @return k_Identity
    */
  function identity() {
    if (!isset($this->identity)) {
      $this->identity = $this->identity_loader->load($this);
    }
    return $this->identity;
  }
  /**
    * Generates a URL relative to this component
    * @param mixed
    * @param array
    * @return string
    */
  function url($path = "", $params = array()) {
    if (is_array($path)) {
      $path = implode('/', array_map('rawurlencode', $path));
    }
    $stack = array();
    foreach (explode('/', $this->href_base . $path) as $name) {
      if ($name == '..' && count($stack) > 0) {
        array_pop($stack);
      } else {
        $stack[] = $name;
      }
    }
    $normalised_path = implode('/', $stack);
    return
      ($normalised_path === $this->href_base ? ($normalised_path . '/') : $normalised_path)
      . (count($params) > 0 ? ('?' . http_build_query($params)) : '');
  }
  /**
    * @return string
    */
  function subspace() {
    return $this->subspace;
  }
  /**
    * @param array
    * @return string
    */
  function negotiateContentType($candidates = array(), $user_override = null) {
    return $this->content_type_negotiator->match($candidates, $user_override);
  }
}

/**
 * Encapsulates logic for comparing against the Accept HTTP-header
 */
class k_ContentTypeNegotiator {
  /** @var array */
  protected $types;
  /**
    * @param string
    * @return oid
    */
  function __construct($accept = "") {
    $this->types = $this->parse($accept);
    usort($this->types, array($this, '_sortByQ'));
  }
  /**
    * @param string
    * @return array
    */
  function parse($input) {
    $types = array();
    foreach (explode(",", $input) as $tuple) {
      if ($tuple) {
        $types[] = $this->parseType($tuple);
      }
    }
    return $types;
  }
  /**
    * @param string
    * @return array
    */
  function parseType($tuple) {
    $tuple = trim($tuple);
    if (preg_match('~^(.*)/([^; ]*)(\s*;\s*q\s*=\s*([0-9.]+))?$~', $tuple, $mm)) {
      return array($mm[1] . '/' . $mm[2], $mm[1], $mm[2], isset($mm[4]) ? $mm[4] : 1);
    }
    return array($tuple, $tuple, '*', 1);
  }
  /**
    * @param array
    * @param array
    * @return integer
    */
  function _sortByQ($a, $b) {
    if ($a[3] === $b[3]) {
      return 0;
    }
    return ($a[3] > $b[3]) ? -1 : 1;
  }
  /**
    * @param array
    * @param array
    * @return boolean
    */
  function compare($a, $b) {
    return ($a[1] === $b[1] || $a[1] === '*' || $b[1] === '*') && ($a[2] === $b[2] || $a[2] === '*' || $b[2] === '*');
  }
  /**
    * @param array
    * @return string
    */
  function match($candidates = array(), $user_override = null) {
    if ($user_override) {
      $types = $this->parse($user_override);
    } else {
      $types = $this->types;
    }
    if (count($types) == 0 && count($candidates) > 0) {
      return $candidates[0];
    }
    foreach ($types as $type) {
      foreach ($candidates as $candidate) {
        $candidate_type = $this->parseType($candidate);
        if ($this->compare($candidate_type, $type)) {
          return $candidate_type[0];
        }
      }
    }
  }
}

/**
 * The document is a container for properties of the HTML-document.
 * The default implementation (this) is rather limited. If you have more specific needs,
 * you can add you own subclass and use that instead. In that case, you should follow the
 * same convention of explicit getters/setters, rather than using public properties etc.
 */
class k_Document {
  /** @var string */
  protected $title = "No Title";
  /** @var array */
  protected $scripts = array();
  /** @var array */
  protected $styles = array();
  /** @var array */
  protected $onload = array();
  function title() {
    return $this->title;
  }
  function setTitle($title) {
    return $this->title = $title;
  }
  function scripts() {
    return $this->scripts;
  }
  function addScript($script) {
    return $this->scripts[] = $script;
  }
  function styles() {
    return $this->styles;
  }
  function addStyle($style) {
    return $this->styles[] = $style;
  }
  function onload() {
    return $this->onload;
  }
  function addOnload($onload) {
    return $this->onload[] = $onload;
  }
  function __set($name, $value) {
    throw new Exception("Setting of undefined property not allowed");
  }
}

/**
 * Exception is raised when trying to change an immutable property.
 */
class k_PropertyImmutableException extends Exception {
  /** @var string */
  protected $message = 'Tried to change immutable property after initial assignment';
}

/**
 * A component is the baseclass for all userland components
 * Each component should be completely isolated from its surrounding, only
 * depending on its parent context
 */
abstract class k_Component implements k_Context {
  /** @var k_Context */
  protected $context;
  /** @var k_UrlState */
  protected $url_state;
  /**
   * UrlState, will be initialised with these values, upon creation.
   * @var array
   */
  protected $url_init = array();
  /** @var k_ComponentCreator */
  protected $component_creator;
  /** @var k_Document */
  protected $document;
  /** @var k_DebugListener */
  protected $debugger;
  /**
   * Log something to the debugger.
   */
  protected function debug($mixed) {
    $this->debugger->log($mixed);
  }
  /**
    * @param k_Context
    * @return void
    */
  function setContext(k_Context $context) {
    if ($this->context !== null) {
      throw new k_PropertyImmutableException();
    }
    $this->context = $context;
  }
  /**
    * @param k_UrlState
    * @return void
    */
  function setUrlState(k_UrlState $url_state) {
    if ($this->url_state !== null) {
      throw new k_PropertyImmutableException();
    }
    $this->url_state = $url_state;
    foreach ($this->url_init as $key => $value) {
      $this->url_state->init($key, $value);
    }
  }
  /**
    * @param k_ComponentCreator
    * @return void
    */
  function setComponentCreator(k_ComponentCreator $component_creator) {
    if ($this->component_creator !== null) {
      throw new k_PropertyImmutableException();
    }
    $this->component_creator = $component_creator;
  }
  /**
    * @param k_Document
    * @return void
    */
  function setDocument(k_Document $document) {
    if ($this->document !== null) {
      throw new k_PropertyImmutableException();
    }
    $this->document = $document;
  }
  /**
    * @param k_DebugListener
    * @return void
    */
  function setDebugger(k_DebugListener $debugger) {
    $this->debugger = $debugger;
  }
  /**
    * @param string
    * @param mixed   The default value to return, if the value doesn't exist
    * @return string
    */
  function query($key = null, $default = null) {
    return $this->url_state->get($key, $default);
  }
  /**
    * @param string
    * @param string
    * @return string
    */
  function body($key = null, $default = null) {
    return $this->context->body($key, $default);
  }
  function rawHttpRequestBody() {
    return $this->context->rawHttpRequestBody();
  }
  function header($key = null, $default = null) {
    return $this->context->header($key, $default);
  }
  function cookie($key = null, $default = null) {
    return $this->context->cookie($key, $default);
  }
  /**
    * @param string
    * @param mixed   The default value to return, if the value doesn't exist
    * @return string
    */
  function session($key = null, $default = null) {
    return $this->context->session($key, $default);
  }
  function file($key = null, $default = null) {
    return $this->context->file($key, $default);
  }
  /**
    * @return string
    */
  function method() {
    return $this->context->method();
  }
  function requestUri() {
    return $this->context->requestUri();
  }
  /**
    * @return string
    */
  function serverName() {
    return $this->context->serverName();
  }
  function identity() {
    return $this->context->identity();
  }
  /**
    * @param mixed
    * @param array
    * @return string
    */
  function url($path = "", $params = array()) {
    if (is_array($path)) {
      $path = implode('/', array_map('rawurlencode', $path));
    }
    return $this->context->url(
      $path
      ? (substr($path, 0, 1) === '/'
        ? $path
        : ($path === '.'
          ? $this->name()
          : (preg_match('~^\.([^/.]+)~', $path, $mm)
            ? ($this->name() . '.' . $mm[1])
            : $this->segment() . '/' . $path)))
      : $this->segment(),
      $this->url_state->merge($params));
  }
  /**
    * @return string
    */
  function subspace() {
    return preg_replace('~^[^/]*[/]{0,1}~', '', $this->context->subspace());
  }
  function negotiateContentType($candidates = array(), $user_override = null) {
    return $this->context->negotiateContentType($candidates, $user_override);
  }
  protected function contentTypeShortName() {
    $content_type = preg_replace('/^[^;]+(;.*)$/', '', $this->header('content-type'));
    return isset($GLOBALS['konstrukt_content_types'][$content_type]) ? $GLOBALS['konstrukt_content_types'][$content_type] : null;
  }
  /**
    * The full path segment for this components representation.
    * @return string
    */
  protected function segment() {
    if (preg_match('~^([^/]+)[/]{0,1}~', $this->context->subspace(), $mm)) {
      return $mm[1];
    }
    // special case for top-level + subtype
    if (preg_match('~^/(\.[^/]+)[/]{0,1}~', $this->context->subspace(), $mm)) {
      return $mm[1];
    }
  }
  /**
    * The name part of the uri segment.
    * @return string
    */
  protected function name() {
    if ($segment = $this->segment()) {
      return preg_replace('/\..*$/', '', $segment);
    }
  }
  /**
    * @return string
    */
  protected function subtype() {
    if (preg_match('/\.(.+)$/', $this->segment(), $mm)) {
      return $mm[1];
    }
  }
  /**
    * @return string
    */
  protected function next() {
    if (preg_match('~^[^/.]+~', $this->subspace(), $mm)) {
      return $mm[0];
    }
  }
  /**
    * @param string
    * @param string A namespace for querystring parameters
    * @return string
    */
  protected function forward($class_name, $namespace = "") {
    if (is_array($class_name)) {
      $namespace = $class_name[1];
      $class_name = $class_name[0];
    }
    $next = $this->createComponent($class_name, $namespace);
    return $next->dispatch();
  }
  /**
    * @param string
    * @param string
    * @return k_Component
    */
  protected function createComponent($class_name, $namespace) {
    return $this->component_creator->create($class_name, $this, $namespace);
  }
  /**
    * @return string
    */
  function dispatch() {
    $this->debugger->logDispatch($this, $this->name(), $this->next());
    $next = $this->next();
    if ($next) {
      $class_name = $this->map($next);
      if (!$class_name) {
        throw new k_PageNotFound();
      }
      return $this->wrap($this->forward($class_name));
    }
    return $this->execute();
  }
  protected function map($name) {}
  /**
    * @return string
    */
  function execute() {
    $method = $this->method();
    if (!in_array($method, array('head','get','post','put','delete'))) {
      throw new k_MethodNotAllowed();
    }
    return $this->{$method}();
  }
  function HEAD() {
    throw new k_NotImplemented();
  }
  function GET() {
    return $this->render();
  }
  function POST() {
    $postfix = $this->contentTypeShortName();
    if ($postfix) {
      if (method_exists($this, 'post' . $postfix)) {
        return $this->{'post' . $postfix}();
      }
    }
    foreach (get_class_methods($this) as $m) {
      if (preg_match('~^post.+~', $m)) {
        // There is at least one acceptable handler for post
        throw new k_NotAcceptable();
      }
    }
    throw new k_NotImplemented();
  }
  function PUT() {
    $postfix = $this->contentTypeShortName();
    if ($postfix) {
      if (method_exists($this, 'put' . $postfix)) {
        return $this->{'put' . $postfix}();
      }
    }
    foreach (get_class_methods($this) as $m) {
      if (preg_match('~^put.+~', $m)) {
        // There is at least one acceptable handler for put
        throw new k_NotAcceptable();
      }
    }
    throw new k_NotImplemented();
  }
  function DELETE() {
    throw new k_NotImplemented();
  }
  /**
   * Renders the components view.
   * This method delegates control to an appropriate handler, based on content-type negotiation.
   * The typical handler would be `renderHtml`. If no handler is present, an exception is raised.
   */
  function render() {
    $accept = array();
    foreach ($GLOBALS['konstrukt_content_types'] as $type => $name) {
      $handler = 'render' . $name;
      if (method_exists($this, $handler)) {
        $accept[$type] = $handler;
        $accept[$name] = $handler;
      }
    }
    $content_type = $this->negotiateContentType(array_keys($accept), $this->subtype());
    if (isset($accept[$content_type])) {
      return k_coerce_to_response(
        $this->{$accept[$content_type]}(),
        k_content_type_to_response_type($content_type));
    }
    if (count($accept[$content_type]) > 0) {
      throw new k_NotAcceptable();
    }
    throw new k_NotImplemented();
  }
  function wrap($content) {
    $typed = k_coerce_to_response($content);
    $response_type = ($typed instanceof k_HttpResponse) ? 'http' : k_content_type_to_response_type($typed->contentType());
    $handler = 'wrap' . $response_type;
    if (method_exists($this, $handler)) {
      $wrapped = $this->{$handler}($typed->toContentType($typed->internalType()));
      if ($wrapped instanceof k_Response) {
        return $wrapped;
      }
      $wrapped = k_coerce_to_response($wrapped, $response_type);
      $wrapped->setStatus($content->status());
      foreach ($content->headers() as $key => $value) {
        $wrapped->setHeader($key, $value);
      }
      $wrapped->setCharset($content->charset());
      return $wrapped;
    }
    return $content;
  }
}

/**
 * Used for persisting state over the query-string, and for namespacing query-string parameters
 */
class k_UrlState {
  /** @var k_Context */
  protected $context;
  /** @var string */
  protected $namespace;
  /** @var array */
  protected $state = array();
  /** @var array */
  protected $default_values = array();
  /**
    * @param k_Context
    * @param string
    * @return void
    */
  function __construct(k_Context $context, $namespace = "") {
    $this->context = $context;
    $this->namespace = $namespace;
  }
  /**
    * @param string
    * @param string
    * @return void
    */
  function init($key, $default = "") {
    $this->state[$this->namespace . $key] = $this->context->query($this->namespace . $key, (string) $default);
    $this->default_values[$this->namespace . $key] = (string) $default;
  }
  function has($key) {
    return isset($this->state[$this->namespace . $key]);
  }
  /**
    * @param string
    * @param mixed   The default value to return, if the value doesn't exist
    * @return string
    */
  function get($key, $default = null) {
    return isset($this->state[$this->namespace . $key]) ? $this->state[$this->namespace . $key] : $this->context->query($this->namespace . $key, $default);
  }
  /**
    * @param string
    * @param string
    * @return void
    */
  function set($key, $value) {
    $this->state[$this->namespace . $key] = (string) $value;
  }
  /**
    * @param array
    * @return array
    */
  function merge($params = array()) {
    $result = $this->state;
    foreach ($params as $key => $value) {
      $result[$this->namespace . $key] = $value;
    }
    // filter off default values, since they are implied
    foreach ($result as $ns_key => $value) {
      if (isset($this->default_values[$ns_key]) && $value == $this->default_values[$ns_key]) {
        $result[$ns_key] = null;
      }
    }
    return $result;
  }
}

/**
 * A utility class. This is a very simple template engine - Essentially, it's just
 * a wrapper around include, using output buffering to grab and return the output.
 */
class k_Template {
  /** @var string */
  protected $path;
  /**
    * @param string
    * @return void
    */
  function __construct($path) {
    $this->path = $path;
  }
  function output($str) {
    echo htmlspecialchars($str, ENT_QUOTES);
  }
  /**
    * @param k_Context
    * @return string
    */
  function render($context /*, $model = array() */) {
    self::InstallGlobals();
    if (func_num_args() > 1) {
      extract(func_get_arg(1));
    }
    $__old_handler_e__ = $GLOBALS['_global_function_callback_e'];
    $__old_handler_____ = $GLOBALS['_global_function_callback___'];
    $__old_handler_t__ = $GLOBALS['_global_function_callback_t'];
    $__old_handler_url__ = $GLOBALS['_global_function_callback_url'];
    $GLOBALS['_global_function_callback_e'] = array($this, 'output');
    $GLOBALS['_global_function_callback___'] = array($context, '__');
    $GLOBALS['_global_function_callback_t'] = array($context, '__');
    $GLOBALS['_global_function_callback_url'] = array($context, 'url');
    ob_start();
    try {
      if (!self::SearchIncludePath($this->path)) {
        throw new Exception("Unable to find file '" . $this->path . "'");
      }
      include($this->path);
      $buffer = ob_get_clean();
      $GLOBALS['_global_function_callback_e'] = $__old_handler_e__;
      $GLOBALS['_global_function_callback___'] = $__old_handler_____;
      $GLOBALS['_global_function_callback_t'] = $__old_handler_t__;
      $GLOBALS['_global_function_callback_url'] = $__old_handler_url__;
      return $buffer;
    } catch (Exception $ex) {
      ob_end_clean();
      $GLOBALS['_global_function_callback_e'] = $__old_handler_e__;
      $GLOBALS['_global_function_callback___'] = $__old_handler_____;
      $GLOBALS['_global_function_callback_t'] = $__old_handler_t__;
      $GLOBALS['_global_function_callback_url'] = $__old_handler_url__;
      throw $ex;
    }
  }
  /**
    * Searches the include-path for a filename.
    * Returns the absolute path (realpath) if found or FALSE
    * @return mixed
    */
  protected static function SearchIncludePath($filename) {
    if (is_file($filename)) {
      return $filename;
    }
    foreach (explode(PATH_SEPARATOR, ini_get("include_path")) as $path) {
      if (strlen($path) > 0 && $path{strlen($path)-1} != DIRECTORY_SEPARATOR) {
        $path .= DIRECTORY_SEPARATOR;
      }
      $f = realpath($path . $filename);
      if ($f && is_file($f)) {
        return $f;
      }
    }
    return false;
  }
  /**
   * Installs global functions
   */
  protected static function InstallGlobals() {
    // Dynamic global functions
    if (!function_exists('e')) {
      /**
       * This function is dynamically redefinable.
       * @see $GLOBALS['_global_function_callback_e']
       */
      function e($args) {
        $args = func_get_args();
        return call_user_func_array($GLOBALS['_global_function_callback_e'], $args);
      }
      if (!isset($GLOBALS['_global_function_callback_e'])) {
        $GLOBALS['_global_function_callback_e'] = null;
      }
    }

    if (!function_exists('__')) {
      /**
       * This function is dynamically redefinable.
       * @see $GLOBALS['_global_function_callback___']
       */
      function __($args) {
        $args = func_get_args();
        return call_user_func_array($GLOBALS['_global_function_callback___'], $args);
      }
      if (!isset($GLOBALS['_global_function_callback___'])) {
        $GLOBALS['_global_function_callback___'] = null;
      }
    }

    if (!function_exists('t')) {
      /**
       * This function is dynamically redefinable.
       * @see $GLOBALS['_global_function_callback_t']
       */
      function t($args) {
        $args = func_get_args();
        return call_user_func_array($GLOBALS['_global_function_callback_t'], $args);
      }
      if (!isset($GLOBALS['_global_function_callback_t'])) {
        $GLOBALS['_global_function_callback_t'] = null;
      }
    }

    if (!function_exists('url')) {
      /**
       * This function is dynamically redefinable.
       * @see $GLOBALS['_global_function_callback_url']
       */
      function url($args = null) {
        $args = func_get_args();
        return call_user_func_array($GLOBALS['_global_function_callback_url'], $args);
      }
      if (!isset($GLOBALS['_global_function_callback_url'])) {
        $GLOBALS['_global_function_callback_url'] = null;
      }
    }
  }
}

/**
 * A metaresponse represents an abstract event in the application, which needs alternate handling.
 * This would typically be an error-condition.
 * In the simplest invocation, a metaresponse maps directly to a component, which renders a generic error.
 */
abstract class k_MetaResponse extends Exception {
  abstract function componentName();
}

/**
 * Raise this if the user must be authorised to access the requested resource.
 */
class k_NotAuthorized extends k_MetaResponse {
  /** @var string */
  protected $message = 'You must be authorised to access this resource';
  function componentName() {
    return 'k_DefaultNotAuthorizedComponent';
  }
}

/**
 * Raise this if the user doesn't have access to the requested resource.
 */
class k_Forbidden extends k_MetaResponse {
  /** @var string */
  protected $message = 'The requested page is forbidden';
  function componentName() {
    return 'k_DefaultForbiddenComponent';
  }
}

/**
 * Raise this if the requested resource couldn't be found.
 */
class k_PageNotFound extends k_MetaResponse {
  /** @var string */
  protected $message = 'The requested page was not found';
  function componentName() {
    return 'k_DefaultPageNotFoundComponent';
  }
}

/**
 * Raise this if resource doesn't support the requested HTTP method.
 * @see k_NotImplemented
 */
class k_MethodNotAllowed extends k_MetaResponse {
  /** @var string */
  protected $message = 'The request HTTP method is not supported by the handling component';
  function componentName() {
    return 'k_DefaultMethodNotAllowedComponent';
  }
}

/**
 * Raise this if the request isn't yet implemented.
 * This is roughly the HTTP equivalent to a "todo"
 */
class k_NotImplemented extends k_MetaResponse {
  /** @var string */
  protected $message = 'The server does not support the functionality required to fulfill the request';
  function componentName() {
    return 'k_DefaultNotImplementedComponent';
  }
}

/**
 * Raise this if the request can't be processed due to details of the request (Such as the Content-Type or other headers)
 */
class k_NotAcceptable extends k_MetaResponse {
  /** @var string */
  protected $message = 'The resource identified by the request is only capable of generating response entities which have content characteristics not acceptable according to the accept headers sent in the request';
  function componentName() {
    return 'k_DefaultNotNotAcceptableComponent';
  }
}

/**
 * @see k_NotAuthorized
 */
class k_DefaultNotAuthorizedComponent extends k_Component {
  function dispatch() {
    $response = $this->render();
    $response->setStatus(401);
    $response->setHeader('WWW-Authenticate', 'Basic realm="Restricted"');
    return $response;
  }
  function renderHtml() {
    return '<html><body><h1>HTTP 401 - Not Authorized</h1></body></html>';
  }
}

/**
 * @see k_Forbidden
 */
class k_DefaultForbiddenComponent extends k_Component {
  function dispatch() {
    $response = $this->render();
    $response->setStatus(403);
    return $response;
  }
  function renderHtml() {
    return '<html><body><h1>HTTP 403 - Forbidden</h1></body></html>';
  }
}

/**
 * @see k_PageNotFound
 */
class k_DefaultPageNotFoundComponent extends k_Component {
  function dispatch() {
    $response = $this->render();
    $response->setStatus(404);
    return $response;
  }
  function renderHtml() {
    return '<html><body><h1>HTTP 404 - Page Not Found</h1></body></html>';
  }
}

/**
 * @see k_MethodNotAllowed
 */
class k_DefaultMethodNotAllowedComponent extends k_Component {
  function dispatch() {
    $response = $this->render();
    $response->setStatus(405);
    return $response;
  }
  function renderHtml() {
    return '<html><body><h1>HTTP 405 - Method Not Allowed</h1></body></html>';
  }
}

/**
 * @see k_NotAcceptable
 */
class k_DefaultNotNotAcceptableComponent extends k_Component {
  function dispatch() {
    $response = $this->render();
    $response->setStatus(406);
    return $response;
  }
  function renderHtml() {
    return '<html><body><h1>HTTP 406 - Not Acceptable</h1></body></html>';
  }
}

/**
 * @see k_NotImplemented
 */
class k_DefaultNotImplementedComponent extends k_Component {
  function dispatch() {
    $response = $this->render();
    $response->setStatus(501);
    return $response;
  }
  function renderHtml() {
    return '<html><body><h1>HTTP 501 - Not Implemented</h1></body></html>';
  }
}

/**
 * Creates an application bootstrap.
 * @return k_Bootstrap
 */
function k() {
  return new k_Bootstrap();
}

/**
 * Application bootstrap.
 */
class k_Bootstrap {
  /** @var k_HttpRequest */
  protected $http_request;
  /** @var k_ComponentCreator */
  protected $components;
  /** @var k_charset_CharsetStrategy */
  protected $charset_strategy;
  /** @var boolean */
  protected $is_debug = false;
  /** @var string */
  protected $log_filename = null;
  /** @var string */
  protected $href_base = null;
  /** @var k_IdentityLoader */
  protected $identity_loader;
  /** @var k_adapter_GlobalsAccess */
  protected $globals_access;
  /**
   * Serves a http request, given a root component name.
   * @param $root_class_name   string   The classname of an instance of k_Component
   * @return k_Response
   */
  function run($root_class_name) {
    $debugger = new k_MultiDebugListener();
    $this->components()->setDebugger($debugger);
    if ($this->is_debug) {
      $debugger->add(new k_logging_WebDebugger());
    }
    if ($this->log_filename) {
      $debugger->add(new k_logging_LogDebugger($this->log_filename));
    }
    try {
      $debugger->logRequestStart($this->context());
      return $debugger->decorate($this->dispatchRoot($root_class_name));
    } catch (Exception $ex) {
      $debugger->logException($ex);
      throw $ex;
    }
  }
  protected function dispatchRoot($root_class_name) {
    try {
      $class_name = $root_class_name;
      while (true) {
        try {
          $root = $this->components()->create($class_name, $this->context());
          $response = $root->dispatch();
          if (!($response instanceof k_Response)) {
            $response = new k_HtmlResponse($response);
          }
          $response->setCharset($this->charsetStrategy()->responseCharset());
          return $response;
        } catch (k_MetaResponse $ex) {
          $class_name = $ex->componentName();
        }
      }
    } catch (k_Response $ex) {
      return $ex;
    }
  }
  /**
    * Sets the context to use. Usually, this is an instance of k_HttpRequest.
    * @param k_Context
    * @return k_Bootstrap
    */
  function setContext(k_Context $http_request) {
    $this->http_request = $http_request;
    return $this;
  }
  /**
    * Sets the componentcreator to use.
    * @param k_ComponentCreator
    * @return k_Bootstrap
    */
  function setComponentCreator(k_ComponentCreator $components) {
    $this->components = $components;
    return $this;
  }
  /**
    * Set the charsetstrategy.
    * @param k_charset_CharsetStrategy
    * @return k_Bootstrap
    */
  function setCharsetStrategy(k_charset_CharsetStrategy $charset_strategy) {
    $this->charset_strategy = $charset_strategy;
    return $this;
  }
  /**
    * Set the identity loader.
    * @param k_IdentityLoader
    * @return k_Bootstrap
   */
  function setIdentityLoader(k_IdentityLoader $identity_loader) {
    $this->identity_loader = $identity_loader;
    return $this;
  }
  /**
    * Enable/disable the in-browser debug-bar.
    * @param boolean
    * @return k_Bootstrap
    */
  function setDebug($is_debug = true) {
    $this->is_debug = !! $is_debug;
    return $this;
  }
  /**
    * Specifies a filename to log debug information to.
    * @param string
    * @return k_Bootstrap
    */
  function setLog($filename) {
    $this->log_filename = $filename;
    return $this;
  }
  /**
    * Sets the base href, if the application isn't mounted at the web root.
    * @param string
    * @return k_Bootstrap
    */
  function setHrefBase($href_base) {
    $this->href_base = $href_base;
    return $this;
  }
  /**
    * @return k_Context
    */
  protected function context() {
    if (!isset($this->http_request)) {
      $this->http_request = new k_HttpRequest($this->href_base, null, $this->identityLoader(), $this->globalsAccess());
    }
    return $this->http_request;
  }
  /**
    * @return k_ComponentCreator
    */
  protected function components() {
    if (!isset($this->components)) {
      $this->components = new k_DefaultComponentCreator();
    }
    return $this->components;
  }
  /**
    * @return k_charset_CharsetStrategy
    */
  protected function charsetStrategy() {
    if (!isset($this->charset_strategy)) {
      $this->charset_strategy = new k_charset_Utf8CharsetStrategy();
    }
    return $this->charset_strategy;
  }
  /**
    * @return k_IdentityLoader
    */
  protected function identityLoader() {
    if (!isset($this->identity_loader)) {
      $this->identity_loader = new k_DefaultIdentityLoader();
    }
    return $this->identity_loader;
  }
  /**
    * @return k_adapter_GlobalsAccess
    */
  protected function globalsAccess() {
    if (!isset($this->globals_access)) {
      $this->globals_access = new k_adapter_SafeGlobalsAccess($this->charsetStrategy());
    }
    return $this->globals_access;
  }
}

/**
 * Resolves a filename according to the includepath.
 * Returns on the first match or false if no match is found.
 */
function k_search_include_path($filename) {
  if (is_file($filename)) {
    return $filename;
  }
  foreach (explode(PATH_SEPARATOR, ini_get("include_path")) as $path) {
    if (strlen($path) > 0 && $path{strlen($path)-1} != DIRECTORY_SEPARATOR) {
      $path .= DIRECTORY_SEPARATOR;
    }
    $f = realpath($path . $filename);
    if ($f && is_file($f)) {
      return $f;
    }
  }
  return false;
}

/**
 * A simple autoloader.
 */
function k_autoload($classname) {
  $filename = str_replace('_', '/', strtolower($classname)).'.php';
  if (k_search_include_path($filename)) {
    require_once($filename);
  }
}

/**
 * An error-handler which converts all errors (regardless of level) into exceptions.
 * It respects error_reporting settings.
 */
function k_exceptions_error_handler($severity, $message, $filename, $lineno) {
  if (error_reporting() == 0) {
    return;
  }
  if (error_reporting() & $severity) {
    throw new ErrorException($message, 0, $severity, $filename, $lineno);
  }
}
