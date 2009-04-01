<?php
require_once 'konstrukt/charset.inc.php';

class k_ImpossibleContentTypeConversionException extends Exception {}
class k_ResponseToStringConversionException extends Exception {}
class k_CharsetMismatchException extends Exception {}

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
    return new k_HttpRequest(200, $maybe_response);
  }
  $class = 'k_' . $response_type . 'response';
  return new $class($maybe_response);
}

interface k_Response {
  function encoding();
  function internalType();
  function contentType();
  /**
   * Marshals the content to text or other native type.
   * Raises an exception if it isn't possible to convert into the target type.
   */
  function toInternalRepresentation($content_type);
  function out(k_adapter_OutputAccess $output = null);
}

abstract class k_BaseResponse extends Exception implements k_Response {
  protected $content;
  protected $status = 200;
  protected $headers = array();
  protected $charset;
  function __construct($content /*, [...] */) {
    foreach (func_get_args() as $arg) {
      if ($arg instanceof k_Response) {
        if (!$this->charset) {
          $this->charset = $arg->charset();
        }
        if ($this->encoding() !== $arg->encoding()) {
          throw new k_CharsetMismatchException(); // todo: if possible, convert
        }
        $this->content .= $arg->toInternalRepresentation($this->internalType());
      } else {
        if (!$this->charset) {
          $this->charset = new k_charset_Utf8();
        }
        $this->content .= $arg;
      }
    }
    if (!$this->charset) {
      $this->charset = new k_charset_Utf8();
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
    return $this->status;
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
    if (isset($this->content_type)) {
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
    $output->write($this->charset->encode($this->content));
  }
}

class k_HttpResponse extends k_BaseResponse {
  function __construct($status = 200, $content = "", $input_is_utf8 = false) {
    if (!is_string($content)) {
      //      try {
      throw new Exception("Illegal 2 argument - Expected string, but got a " . gettype($content));
      //      } catch (Exception $ex) {
      //        print $ex->getTraceAsString();
      //        throw $ex;
      //      }
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
  function toInternalRepresentation($content_type) {
    throw new k_ImpossibleContentTypeConversionException();
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

class k_HtmlResponse extends k_BaseResponse {
  function contentType() {
    return 'text/html';
  }
  function toInternalRepresentation($content_type) {
    switch ($content_type) {
    case 'text/html':
      return $this->content;
    }
    throw new k_ImpossibleContentTypeConversionException();
  }
}

class k_TextResponse extends k_BaseResponse {
  function contentType() {
    return 'text/text';
  }
  function toInternalRepresentation($content_type) {
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
  function __construct($content /*, [...] */) {
    $multiples = array();
    foreach (func_get_args() as $arg) {
      if ($arg instanceof k_BaseResponse) {
        $multiples[] = $arg->toInternalRepresentation($this->internalType());
      } else {
        $multiples[] = $arg;
      }
    }
    if (count($multiples) === 1) {
      $this->content = $multiples[0];
    } else {
      $this->content = $multiples;
    }
  }
  function internalType() {
    return 'x-application/konstrukt+struct';
  }
  function toInternalRepresentation($content_type) {
    if ($content_type === 'x-application/konstrukt+struct') {
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
