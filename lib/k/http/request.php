<?php
/**
  * Encapsulates the incomming HTTP-request.
  *
  * A single instance of this class is normally used as the top-level context in
  * an application. It has the interface of a [[context|#class-k_icontext]], but is not a controller.
  *
  * Input variables are automatically reverted, if the magic-quotes feature is on.
  * Since Konstrukt assumes all input to be UTF-8 encoded, it is converted
  * from UTF-8 to ISO-8859-1 (The internal charset of PHP). Therefore, if you use
  * another source, than a UTF-8 encoded form to provide input data, make sure to
  * send content encoded with UTF-8. This is normally the default encoding for
  * non-browser clients (Such as XmlHttpRequest), and so should just be left to its
  * defaults.
  *
  * The request creates a registry, and registers a number of input sources with it,
  * making them to any controllers, which use the httprequest as context. The available
  * datasources are:
  *   GET, POST, FILES, HEADERS, ENV, INPUT
  * The three first are copies of PHP's superglobals, $_GET, $_POST, $_FILES
  * HEADERS contains all headers of the http request
  * ENV contains environment variables. Corresponds to the $_SERVER superglobal, but
  * adds a few fields.
  * INPUT contains the raw input body of the request. This corresponds to ``php://input``
  */
class k_http_Request implements k_iContext
{
  /**
    * @var k_Registry
    */
  protected $registry = NULL;
  /**
    * The URI-subspace for this controller.
    * @var string
    */
  protected $subspace = "";
  /**
    * @var k_UrlBuilder
    */
  protected $urlBuilder;
  /**
    * @var k_UrlStateSource
    */
  protected $state;

  function __construct(k_Registry $registry = null) {
    $this->registry = $registry ? $registry : new k_Registry();

    // workaround for wierd "undocumented feature" of IE
    $HEADERS = Array();
    if (function_exists('apache_request_headers')) {
      foreach (apache_request_headers() as $key => $value) {
        for ($tmp = explode("-", strtolower($key)), $i=0; $i<count($tmp); ++$i) {
          $tmp[$i] = ucfirst($tmp[$i]);
        }
        $HEADERS[implode("-", $tmp)] = $value;
      }
    }

    $ENV = $_SERVER;
    if (isset($HEADERS['Http-Method-Equivalent'])) {
      $ENV['K_HTTP_METHOD'] = $HEADERS['Http-Method-Equivalent'];
    } else {
      $ENV['K_HTTP_METHOD'] = $ENV['REQUEST_METHOD'];
    }

//    if ($ENV['K_HTTP_METHOD'] == "PUT" || $ENV['K_HTTP_METHOD'] == "POST") {
      $INPUT = file_get_contents('php://input');
//    } else {
//      $INPUT = NULL;
//    }

    $protocol = (isset($ENV['HTTPS']) && $ENV['HTTPS'] == "on") ? "https" : "http";
    if (isset($ENV['HTTP_HOST'])) {
      $ENV['K_HTTP_ROOT'] = rtrim($protocol."://".$ENV['HTTP_HOST'], "/");
    } else {
      $ENV['K_HTTP_ROOT'] = $protocol."://".$ENV['SERVER_NAME'];
    }

    $root = rtrim(str_replace("\\", "/", dirname($ENV['PHP_SELF'])), "/")."/";
    $ENV['K_URL_BASE'] = $ENV['K_HTTP_ROOT'] . $root;
    $this->state = new k_UrlStateSource($this);
    $this->urlBuilder = new k_UrlBuilder($ENV['K_URL_BASE'], $this->state);
    $this->subspace = trim(preg_replace("/^(".preg_quote($root,"/").")([^\?]*)(.*)/", "\$2", rawurldecode($ENV['REQUEST_URI'])), "/");

    $this->registry->set('GET', new ArrayObject($this->decodeCharset($this->unMagic($_GET)), ArrayObject::ARRAY_AS_PROPS | ArrayObject::STD_PROP_LIST));
    $this->registry->set('POST', new ArrayObject($this->decodeCharset($this->unMagic($_POST)), ArrayObject::ARRAY_AS_PROPS | ArrayObject::STD_PROP_LIST));
    $this->registry->set('FILES', new ArrayObject($this->decodeCharset($_FILES), ArrayObject::ARRAY_AS_PROPS | ArrayObject::STD_PROP_LIST));
    $this->registry->set('HEADERS', new ArrayObject($this->decodeCharset($HEADERS), ArrayObject::ARRAY_AS_PROPS | ArrayObject::STD_PROP_LIST));
    $this->registry->set('ENV', new ArrayObject($this->decodeCharset($ENV), ArrayObject::ARRAY_AS_PROPS | ArrayObject::STD_PROP_LIST));
    $INPUT = $this->decodeCharset($INPUT);
    $this->registry->set('INPUT', is_array($INPUT) ? new ArrayObject($INPUT, ArrayObject::ARRAY_AS_PROPS | ArrayObject::STD_PROP_LIST) : $INPUT);

    $this->registry->registerAlias('session', 'k_http_Session');
//    $this->registry->registerAlias('cookies', 'k_http_Cookies');
  }

  protected function unMagic($input) {
    if (is_array($input)) {
      $output = Array();
      foreach ($input as $key => $value) {
        $output[$key] = $this->unMagic($value);
      }
      return $output;
    }
    if (get_magic_quotes_gpc()) {
      return stripslashes($input);
    }
    return $input;
  }

  protected function decodeCharset($input) {
    if (is_array($input)) {
      $output = Array();
      foreach ($input as $key => $value) {
        $output[$key] = $this->decodeCharset($value);
      }
      return $output;
    }
    return utf8_decode($input);
  }

  function url($href = "", $args = Array()) {
    return $this->urlBuilder->url($href, $args);
  }

  function getSubspace() {
    return $this->subspace;
  }

  function getRegistry() {
    return $this->registry;
  }

  function getUrlStateContainer($namespace = "") {
    return new k_UrlState($this->state, $namespace);
  }
}
