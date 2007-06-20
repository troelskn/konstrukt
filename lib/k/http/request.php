<?php
/**
  *
  */
class k_http_Request implements k_iContext
{
  /**
    * @var k_Registry
    */
  protected $registry = NULL;
  /**
    * The URI-subspace for this controller.
    *
    * Is normally assigned from the creating context.
    * @var string
    */
  protected $subspace = "";

  function __construct() {
    // workaround for wierd "undocumented feature" of IE
    $HEADERS = Array();
    foreach (apache_request_headers() as $key => $value) {
      for ($tmp = explode("-", strtolower($key)), $i=0;$i<count($tmp);$i++) {
        $tmp[$i] = ucfirst($tmp[$i]);
      }
      $HEADERS[implode("-", $tmp)] = $value;
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
    $ENV['K_URL_BASE'] = $ENV['K_HTTP_ROOT'].$root;
    $this->subspace = trim(preg_replace("/^(".preg_quote($root,"/").")([^\?]*)(.*)/", "\$2", rawurldecode($ENV['REQUEST_URI'])), "/");
    $this->subspace = $this->decodeCharset($this->subspace);

    $this->registry = new k_Registry();
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
    $href = (string) $href;
    $hash = NULL;
    if (preg_match('/^(.*)#(.*)$/', $href, $matches)) {
      preg_match('/^(.*)#(.*)$/', $href, $matches);
      $href = $matches[1];
      $hash = $matches[2];
    }

    // re-parse path to normalize relative symbols
    $href = rtrim($href, "?");
    $href = trim($href, "/");
    $parts = Array();
    foreach (explode("/", $href) as $part) {
      if ($part == '..') {
        if (count($parts) == 0) {
          throw new Exception("Illegal path. Relative level extends below root.");
        }
        array_pop($parts);
      } else {
        $parts[] = $part;
      }
    }
    $href = implode("/", $parts);
    
    $href = $this->registry->ENV['K_URL_BASE'].$href;
    if (!$args) {
      return $href;
    }
    if (preg_match("/(.*)\\?(.*)/", $href, $matches)) {
      $href = $matches[1];
      parse_str($matches[2], $parsed);
      $args = array_merge($parsed, $args);
    }
    $params = Array();
    foreach ($args as $key => $value) {
      if (!is_null($value)) {
        if (is_integer($key)) {
          $params[] = rawurlencode($value);
        } else {
          $params[] = rawurlencode($key)."=".rawurlencode($value);
        }
      }
    }
    if (count($params) > 0) {
      if (strpos($href, "?") === FALSE) {
        $href .= "?".implode("&", $params);
      } else {
        $href .= "&".implode("&", $params);
      }
    }
    if ($hash) {
      $href .= "#".$hash;
    }
    return $href;
  }

  function getSubspace() {
    return $this->subspace;
  }

  function getRegistry() {
    return $this->registry;
  }
}
