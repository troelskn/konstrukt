<?php
require_once 'konstrukt/charset.inc.php';

  /**
   * Mappings between content-types and handler names. If you need to support exotic content-types, you can add to this array.
   * Note: Theese are just a random selection that I thought might be useful .. you can override in the concrete component, to supply your own.
   * See also http://rest.blueoxen.net/cgi-bin/wiki.pl?WhichContentType
   * @var array
   */
$GLOBALS['konstrukt_content_types'] = array(
  'text/html' => 'html',
  'text/html+edit' => 'edit',
  'text/x-html-fragment' => 'fragment',
  'text/xml' => 'xml',
  'text/plain' => 'text',
  'text/csv' => 'csv',
  'text/x-vcard' => 'vcard',
  'application/atom+xml' => 'atom',
  'application/calendar+xml' => 'xcal',
  'application/rdf+xml' => 'rdf',
  'application/json' => 'json',
  'application/pdf' => 'pdf',
  'image/svg+xml' => 'svg',
  'multipart/form-data' => 'multipart',
  'application/x-www-form-urlencoded' => 'form',
  'application/json' => 'json',
  'application/x-serialized-php' => 'php',
);

/**
 * Ensures that the input is a k_Response.
 * Use if the input is not known whether it's a primitive or a k_Response
 */
function k_coerce_to_response($maybe_response, $response_type = 'html') {
  if ($maybe_response instanceof k_Response) {
    return $maybe_response;
  }
  if ($response_type === 'http') {
    // This is to preserve BC interface for httpresponse
    return new k_HttpResponse(200, $maybe_response);
  }
  $class = 'k_' . $response_type . 'response';
  return new $class($maybe_response);
}

function k_content_type_to_response_type($content_type) {
  return
    isset($GLOBALS['konstrukt_content_types'][$content_type])
    ? $GLOBALS['konstrukt_content_types'][$content_type]
    : (in_array($content_type, $GLOBALS['konstrukt_content_types'])
       ? $content_type
       : 'http');
}

class k_ImpossibleContentTypeConversionException extends Exception {}
class k_ResponseToStringConversionException extends Exception {}

interface k_Response {
  function status();
  function headers();
  function encoding();
  function internalType();
  function contentType();
  /**
   * Marshals the content to text or other native type.
   * Raises an exception if it isn't possible to convert into the target type.
   */
  function toContentType($content_type);
  function out(k_adapter_OutputAccess $output = null);
}

abstract class k_BaseResponse implements k_Response {
  protected $content;
  protected $status = 200;
  protected $headers = array();
  protected $charset;
  function __construct($content) {
    if ($content instanceof k_Response) {
      $this->status = $content->status();
      $this->headers = $content->headers();
      $this->charset = $content->charset();
      $this->content = $content->toContentType($this->internalType());
    } else {
      $this->charset = new k_charset_Utf8();
      $this->content = $content;
    }
  }
  function charset() {
    return $this->charset;
  }
  function setCharset($charset) {
    $this->charset = $charset;
  }
  function status() {
    return $this->status;
  }
  function setStatus($status) {
    $this->status = $status;
  }
  function headers() {
    return $this->headers;
  }
  function setHeader($key, $value) {
    $key = strtolower($key);
    if ($key == 'content-type') {
      throw new Exception("Can't set Content-Type header directly. Use setContentType() and setCharset().");
    }
    return $this->headers[$key] = $value;
  }
  function encoding() {
    return $this->charset->name();
  }
  function internalType() {
    return $this->contentType();
  }
  /** Response can't be loosely cast into string.  */
  function __toString() {
    throw new k_ResponseToStringConversionException();
  }
  /**
    * @param k_adapter_OutputAccess
    * @return void
    */
  function out(k_adapter_OutputAccess $output = null) {
    if (!$output) {
      $output = new k_adapter_DefaultOutputAccess();
    }
    $output->endSession();
    $this->sendStatus($output);
    $this->sendHeaders($output);
    $this->sendBody($output);
  }
  /**
    * @param k_adapter_OutputAccess
    * @return void
    */
  protected function sendStatus(k_adapter_OutputAccess $output) {
    switch ($this->status) {
      case 400 :  $statusmsg = "Bad Request";
            break;
      case 401 :  $statusmsg = "Unauthorized";
            break;
      case 403 :  $statusmsg = "Forbidden";
            break;
      case 404 :  $statusmsg = "Not Found";
            break;
      case 405 :  $statusmsg = "Method Not Allowed";
            break;
      case 406 :  $statusmsg = "Not Acceptable";
            break;
      case 410 :  $statusmsg = "Gone";
            break;
      case 412 :  $statusmsg = "Precondition Failed";
            break;
      case 500 :  $statusmsg = "Internal Server Error";
            break;
      case 501 :  $statusmsg = "Not Implemented";
            break;
      case 502 :  $statusmsg = "Bad Gateway";
            break;
      case 503 :  $statusmsg = "Service Unavailable";
            break;
      case 504 :  $statusmsg = "Gateway Timeout";
            break;
      default :  $statusmsg = "";
    }
    $output->header("HTTP/1.1 " . $this->status . ($statusmsg ? " " . $statusmsg : ""), true, $this->status);
  }
  /**
    * @param k_adapter_OutputAccess
    * @return void
    */
  protected function sendHeaders(k_adapter_OutputAccess $output) {
    if ($this->contentType()) {
      $output->header("Content-Type: " . $this->contentType() . "; charset=" . $this->encoding());
    }
    foreach ($this->headers as $key => $value) {
      if ($value !== null) {
        // normalize header
        for ($tmp = explode("-", $key), $i=0;$i<count($tmp);$i++) {
          $tmp[$i] = ucfirst($tmp[$i]);
        }
        $key = implode("-", $tmp);
        $output->header($key . ": " . $value);
      }
    }
  }
  /**
    * @param k_adapter_OutputAccess
    * @return void
    */
  protected function sendBody(k_adapter_OutputAccess $output) {
    $output->write($this->charset->encode($this->toContentType($this->contentType())));
  }
}

/**
 * This is an un-typed httpresponse. You should avoid using this, and instead use
 * a response that matches the content-type of the response, such as k_HtmlResponse
 * for test/html responses. If a type doesn't exist, please bring to the attention of
 * the developers, and we'll see if it can be added to the library.
 * @deprecated
 */
class k_HttpResponse extends k_BaseResponse {
  protected $content_type = 'text/html';
  function __construct($status = 200, $content = "", $input_is_utf8 = true) {
    if (!is_string($content)) {
      throw new Exception("Illegal 2 argument - Expected string, but got a " . gettype($content));
    }
    $this->status = $status;
    $this->content = $input_is_utf8 ? $content : utf8_encode($content);
    $this->charset = new k_charset_Utf8();
  }
  function setContentType($content_type) {
    return $this->content_type = $content_type;
  }
  function contentType() {
    return $this->content_type;
  }
  function toContentType($content_type) {
    if ($content_type == 'application/octet-stream') {
      return $this->content;
    }
    throw new k_ImpossibleContentTypeConversionException();
  }
  protected function sendBody(k_adapter_OutputAccess $output) {
    $output->write($this->charset->encode($this->content));
  }
}

/**
 * Issues a http redirect of type "301 Moved Permanently"
 * Use this if the URL has changed (Eg. a page has been renamed)
 */
class k_MovedPermanently extends k_HttpResponse {
  function __construct($url) {
    parent::__construct(301);
    $this->setHeader("Location", $url);
  }
}

/**
 * Issues a http redirect of type "303 See Other"
 * Use this type of redirect for redirecting after POST
 */
class k_SeeOther extends k_HttpResponse {
  /**
    * @param string
    * @return void
    */
  function __construct($url) {
    parent::__construct(303);
    $this->setHeader("Location", $url);
  }
}

/**
 * Issues a http redirect of type "307 Temporary Redirect"
 * Use this type of redirect if the destination changes from request to request, or
 * if you want the client to keep using the requested URI in the future.
 * This is a rare type of redirect - If in doubt, you probably should
 * use either of "See Other" or "Moved Permanently"
 */
class k_TemporaryRedirect extends k_HttpResponse {
  function __construct($url) {
    parent::__construct(307);
    $this->setHeader("Location", $url);
  }
}

class k_HtmlResponse extends k_BaseResponse {
  function contentType() {
    return 'text/html';
  }
  function toContentType($content_type) {
    switch ($content_type) {
    case 'text/html':
      return $this->content;
    }
    throw new k_ImpossibleContentTypeConversionException();
  }
}

class k_EditResponse extends k_HtmlResponse {

}

class k_FragmentResponse extends k_HtmlResponse {
  function contentType() {
    return 'text/x-html-fragment';
  }
  function toContentType($content_type) {
    switch ($content_type) {
    case 'text/html':
    case 'text/x-html-fragment':
      return $this->content;
    }
    throw new k_ImpossibleContentTypeConversionException();
  }
}

class k_TextResponse extends k_BaseResponse {
  function contentType() {
    return 'text/text';
  }
  function toContentType($content_type) {
    switch ($content_type) {
    case 'text/text':
      return $this->content;
    case 'text/html':
      return htmlspecialchars($this->content);
    }
    throw new k_ImpossibleContentTypeConversionException();
  }
}

abstract class k_ComplexResponse extends k_BaseResponse {
  protected $content = null;
  function __construct($content) {
    if ($content instanceof k_BaseResponse) {
      $this->status = $content->status();
      $this->headers = $content->headers();
      $this->charset = $content->charset();
      $this->content = $content->toContentType($this->internalType());
    } else {
      $this->charset = new k_charset_Utf8();
      $this->content = $content;
    }
  }
  function internalType() {
    return 'internal/array';
  }
  function toContentType($content_type) {
    if ($content_type === 'internal/array') {
      return $this->content;
    } elseif ($content_type === $this->contentType()) {
      return $this->marshal();
    }
    throw new k_ImpossibleContentTypeConversionException();
  }
  abstract protected function marshal();
}

class k_JsonResponse extends k_ComplexResponse {
  function contentType() {
    return 'application/json';
  }
  protected function marshal() {
    return json_encode($this->content);
  }
}

class k_PhpResponse extends k_ComplexResponse {
  function contentType() {
    return 'application/x-serialized-php';
  }
  protected function marshal() {
    return serialize($this->content);
  }
}

/**
 * XmlResponse can use either SimpleXML or Dom for the internal representation.
 * You can extend this class for specific types of XML.
 */
class k_XmlResponse extends k_BaseResponse {
  protected $content = null;
  function __construct($content = "") {
    if ($content instanceof k_BaseResponse) {
      $this->status = $content->status();
      $this->headers = $content->headers();
      $this->charset = $content->charset();
      $this->content = $content->toContentType($this->internalType());
    } elseif ($content instanceof DomDocument) {
      $this->content = $content;
    } elseif ($content instanceof SimpleXMLElement) {
      $this->content = $content;
    } elseif (is_string($content)) {
      $this->content = new SimpleXMLElement($content);
    } elseif (is_object($content)) {
      throw new Exception("Illegal input type object('" . get_class($content) . "')");
    } else {
      throw new Exception("Illegal input type '" . gettype($content) . "'");
    }
    if (!$this->charset) {
      $this->charset = new k_charset_Utf8();
    }
  }
  function contentType() {
    return 'text/xml';
  }
  function internalType() {
    return 'internal/xml';
  }
  function toContentType($content_type) {
    switch ($content_type) {
    case 'internal/xml':
      return $this->content;
    case 'internal/xml+dom':
      return ($this->content instanceof DomDocument) ? $this->content : dom_import_simplexml($this->content);
    case 'internal/xml+simple':
      return ($this->content instanceof SimpleXMLElement) ? $this->content : simplexml_import_dom($this->content);
    }
    if ($content_type === $this->contentType()) {
      return $this->marshal();
    }
    throw new k_ImpossibleContentTypeConversionException();
  }
  protected function marshal() {
    if ($this->content instanceof DomDocument) {
      $document = $this->content;
    } elseif ($this->content instanceof SimpleXMLElement) {
      $dom_node = dom_import_simplexml($this->content);
      $document = $dom_node->ownerDocument;
    }
    $document->encoding = $this->encoding();
    $document->formatOutput = true;
    return $document->saveXML();
  }
}
