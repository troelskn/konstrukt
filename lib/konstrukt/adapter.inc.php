<?php

// A low-level container for abstracting away http-output
interface k_adapter_OutputAccess {
  function header($string, $replace = true, $http_response_code = null);
  function write($bytes);
  function endSession();
}

class k_adapter_DefaultOutputAccess implements k_adapter_OutputAccess {
  function header($string, $replace = true, $http_response_code = null) {
    if ($http_response_code === null) {
      header($string, $replace);
    } else {
      header($string, $replace, $http_response_code);
    }
  }
  function write($bytes) {
    echo $bytes;
  }
  function endSession() {
    if (session_id()) {
      session_write_close();
    }
  }
}

// A low-level container for abstracting away global input variables
interface k_adapter_GlobalsAccess {
  function query();
  function body();
  function rawHttpRequestBody();
  function server();
  function files();
  function cookie();
  function headers();
}

// The default implementation, which undoes magic-quotes, if present
class k_adapter_SafeGlobalsAccess implements k_adapter_GlobalsAccess {
  /** @var k_charset_CharsetStrategy */
  protected $charset;
  /** @var boolean */
  protected $magic_quotes_gpc;
  /**
    * @param k_charset_CharsetStrategy
    * @param bool
    * @return null
    */
  function __construct(k_charset_CharsetStrategy $charset, $magic_quotes_gpc = null) {
    $this->charset = $charset;
    $this->magic_quotes_gpc = $magic_quotes_gpc === null ? get_magic_quotes_gpc() : $magic_quotes_gpc;
  }
  /**
    * @return array
    */
  function query() {
    return $this->charset->decodeInput($this->unMagic($_GET));
  }
  function body() {
    if ($_SERVER['REQUEST_METHOD'] === 'PUT' && $_SERVER['CONTENT_TYPE'] === 'application/x-www-form-urlencoded') {
      parse_str($this->charset->decodeInput($this->rawHttpRequestBody()), $buffer);
      return $buffer;
    }
    return $this->charset->decodeInput($this->unMagic($_POST));
  }
  function rawHttpRequestBody() {
    static $input = null;
    if (!$input) {
      $input = file_get_contents('php://input');
    }
    return $input;
  }
  function server() {
    return $this->charset->decodeInput($this->unMagic($_SERVER));
  }
  function files() {
    return $this->charset->decodeInput($this->unMagic($this->normalizeFiles($_FILES)));
  }
  function normalizeFiles($files) {
    // Fix so $_FILES['userfile']['name'][$i] becomes $_FILES['userfile'][$i]['name']
    $tmp = array();
    foreach ($files as $key => $file) {
      if (isset($file['tmp_name']) && is_array($file['tmp_name'])) {
        for ($i = 0; $i < count($file['tmp_name']); $i++) {
          $tmp[$i] = array();
          $tmp[$i]['tmp_name'] = $file['tmp_name'][$i];
          $tmp[$i]['name'] = $file['name'][$i];
          $tmp[$i]['type'] = $file['type'][$i];
          $tmp[$i]['size'] = $file['size'][$i];
          $tmp[$i]['error'] = $file['error'][$i];
        }
        $files[$key] = $tmp;
        $tmp = array();
      }
    }
    return $files;
  }
  function cookie() {
    // Weird fact: Cookies - are _always_ singlebyte/latin1, unless explicitly encoded, and thus shouldn't be charset-decoded ???
    // See also:
    //   http://coding.derkeiler.com/Archive/PHP/php.general/2006-10/msg00463.html
    // Follow-up:
    // OK .. not so weird after all. RFC 2616, section 2.2 "Basic Rules" specifies that any TEXT tokens must
    // be iso-8859-1 encoded unless otherwise specified.
    // It seems however, that it's valid to encode header fields with mime-encoding (RFC 2047).
    // Alas, that is mostly unsupported and therefore appears to be heading for deprecation ..
    // References:
    //   http://trac.tools.ietf.org/wg/httpbis/trac/ticket/74
    //   http://stackoverflow.com/questions/324470/http-headers-encoding-decoding-in-java
    return $this->unMagic($_COOKIE);
  }
  function headers() {
    // I think we should decode using imap_mime_header_decode
    if (function_exists('apache_request_headers')) {
      return apache_request_headers();
    }
    $headers = array();
    foreach ($_SERVER as $key => $value) {
      // To support FastCGI
      if ($key === 'CONTENT_TYPE') {
        $headers['Content-Type'] = $value;
      } elseif (substr($key, 0, 5) === "HTTP_") {
        $headername = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', substr($key, 5)))));
        $headers[$headername] = $value;
      }
    }
    return $headers;
  }
  /**
    * @param array
    * @return array
    */
  protected function unMagic($input) {
    if (!$this->magic_quotes_gpc) {
      return $input;
    }
    // http://talks.php.net/show/php-best-practices/26
    $in = array(&$input);
    while (list($k,$v) = each($in)) {
      foreach ($v as $key => $val) {
        if (!is_array($val)) {
          $in[$k][$key] = stripslashes($val);
          continue;
        }
        $in[] =& $in[$k][$key];
      }
    }
    unset($in);
    return $input;
  }
}

// low-level container for abstracting away access to cookies
interface k_adapter_CookieAccess {
  function __construct($domain, $raw);
  function has($key);
  function get($key, $default = null);
  function set($key, $value, $expire = 0, $secure = false, $httponly = false);
  function all();
}

class k_adapter_DefaultCookieAccess implements k_adapter_CookieAccess {
  /** @var string */
  protected $domain;
  /** @var string */
  protected $raw;
  /**
    * @param string
    * @param array
    * @return null
    */
  function __construct($domain, $raw) {
    $this->domain = $domain;
    $this->raw = $raw;
  }
  function has($key) {
    return isset($this->raw[$key]);
  }
  function get($key, $default = null) {
    return isset($this->raw[$key]) ? $this->raw[$key] : $default;
  }
  function set($key, $value, $expire = 0, $secure = false, $httponly = false) {
    if ($value === null) {
      setcookie($key, '', time() - 42000, '/');
      unset($this->raw[$key]);
    } else {
      setcookie($key, $value, $expire, '/', $this->domain, $secure, $httponly);
      $this->raw[$key] = $value;
    }
  }
  function all() {
    return $this->raw;
  }
}

// low-level container for abstracting away access to session
interface k_adapter_SessionAccess {
  function has($key);
  function get($key, $default = null);
  function set($key, $value);
  function close();
  function destroy();
  function sessionId();
  function regenerateId();
}

class k_adapter_DefaultSessionAccess implements k_adapter_SessionAccess {
  /** @var k_adapter_CookieAccess */
  protected $cookie_access;
  /**
    * @param k_adapter_DefaultCookieAccess
    * @return null
    */
  function __construct(k_adapter_CookieAccess $cookie_access) {
    $this->cookie_access = $cookie_access;
  }
  protected function autoStart() {
    if (!session_id()) {
      session_start();
    }
  }
  function has($key) {
    $this->autoStart();
    return isset($_SESSION[$key]);
  }
  function get($key, $default = null) {
    $this->autoStart();
    return isset($_SESSION[$key]) ? $_SESSION[$key] : $default;
  }
  function set($key, $value) {
    $this->autoStart();
    $_SESSION[$key] = $value;
    return $value;
  }
  function close() {
    session_id() && session_write_close();
  }
  function destroy() {
    $this->autoStart();
    $_SESSION = array();
    if ($this->cookie_access->has(session_name())) {
      $this->cookie_access->set(session_name(), null);
    }
    session_destroy();
    $filename = realpath(session_save_path()) . DIRECTORY_SEPARATOR . session_id();
    if (is_file($filename) && is_writable($filename)) {
      unlink($filename);
    }
  }
  function sessionId() {
    $this->autoStart();
    return session_id();
  }
  function regenerateId() {
    return session_regenerate_id();
  }
}

class k_adapter_MockSessionAccess implements k_adapter_SessionAccess {
  /** @var k_adapter_CookieAccess */
  protected $cookie_access;
  /** @var array */
  protected $raw = array();
  /**
    * @param k_adapter_MockCookieAccess
    * @param ???
    * @return null
    */
  function __construct(k_adapter_CookieAccess $cookie_access, $raw = array()) {
    $this->cookie_access = $cookie_access;
    $this->raw = $raw;
  }
  function has($key) {
    return isset($this->raw[$key]);
  }
  /**
    * @param string
    * @param ???
    * @return string
    */
  function get($key, $default = null) {
    return isset($this->raw[$key]) ? $this->raw[$key] : $default;
  }
  /**
    * @param string
    * @param string
    * @return string
    */
  function set($key, $value) {
    $this->raw[$key] = $value;
    return $value;
  }
  function close() {}
  function destroy() {
    $this->raw = array();
    if ($this->cookie_access->has('session_id')) {
      $this->cookie_access->set('session_id', null);
    }
  }
  function sessionId() {
    if (!$this->cookie_access->has('session_id')) {
      $this->regenerateId();
    }
    return $this->cookie_access->get('session_id');
  }
  function regenerateId() {
    $this->cookie_access->set('session_id', rand());
    return $this->cookie_access->get('session_id');
  }
}

// Used during testing
class k_adapter_MockGlobalsAccess implements k_adapter_GlobalsAccess {
  /** @var array */
  public $query;
  /** @var array */
  public $body;
  /** @var string */
  public $rawHttpRequestBody;
  /** @var array */
  public $server;
  /** @var array */
  public $headers;
  /** @var array */
  public $cookie;
  /** @var array */
  public $files;
  /**
    * @param array
    * @param array
    * @param array
    * @param array
    * @param array
    * @param array
    */
  function __construct($query = array(), $body = array(), $server = array(), $headers = array(), $cookie = array(), $files = array()) {
    $this->query = $query;
    $this->body = $body;
    $this->server = $server;
    $this->headers = $headers;
    $this->cookie = $cookie;
    $this->files = $files;
  }
  /**
    * @return array
    */
  function query() {
    return $this->query;
  }
  /**
    * @return array
    */
  function body() {
    return $this->body;
  }
  /**
    * @return string
    */
  function rawHttpRequestBody() {
    return $this->rawHttpRequestBody;
  }
  /**
    * @return array
    */
  function server() {
    return $this->server;
  }
  /**
    * @return array
    */
  function headers() {
    return $this->headers;
  }
  /**
    * @return array
    */
  function files() {
    return $this->files;
  }
  /**
    * @return array
    */
  function cookie() {
    return $this->cookie;
  }
}

// For testing
class k_adapter_MockCookieAccess extends k_adapter_DefaultCookieAccess {
  function set($key, $value, $expire = 0, $secure = false, $httponly = false) {
    if ($value === null) {
      unset($this->raw[$key]);
    } else {
      $this->raw[$key] = $value;
    }
  }
}

// Interface for writing uploaded files to filesystem
interface k_adapter_UploadedFileAccess {
  function copy($tmp_name, $path_destination);
}

// Default implementation
class k_adapter_DefaultUploadedFileAccess implements k_adapter_UploadedFileAccess {
  function copy($tmp_name, $path_destination) {
    $this->ensureDirectory(dirname($path_destination));
    if (is_uploaded_file($tmp_name)) {
      move_uploaded_file($tmp_name, $path_destination);
    } else {
      throw new Exception("Fileinfo is not a valid uploaded file");
    }
  }
  protected function mkdir($path) {
    mkdir($path);
  }
  protected function ensureDirectory($dir) {
    if (!is_dir($dir)) {
      $this->ensureDirectory(dirname($dir));
      $this->mkdir($dir);
    }
  }
}

// For testing
class k_adapter_MockUploadedFileAccess implements k_adapter_UploadedFileAccess {
  public $actions = array();
  function copy($tmp_name, $path_destination) {
    $this->actions[] = array($tmp_name, $path_destination);
  }
}

// Wrapper around an uploaded file
class k_adapter_UploadedFile {
  protected $key;
  protected $name;
  protected $tmp_name;
  protected $size;
  protected $type;
  protected $file_access;
  function __construct($file_data, $key, k_adapter_UploadedFileAccess $file_access) {
    $this->key = $key;
    $this->name = $file_data['name'];
    $this->tmp_name = $file_data['tmp_name'];
    $this->size = $file_data['size'];
    $this->type = $file_data['type'];
    $this->file_access = $file_access;
  }
  function __serialize() {
    throw new Exception("Can't serialize an uploaded file. Copy file to a permanent storage.");
  }
  function key() {
    return $this->key;
  }
  function name() {
    return $this->name;
  }
  function type() {
    return $this->type;
  }
  function size() {
    return $this->size;
  }
  function writeTo($path_destination) {
    if ($this->size() === 0) {
      throw new Exception("Filesize is zero");
    }
    $this->file_access->copy($this->tmp_name, $path_destination);
  }
}

