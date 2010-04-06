<?php
require_once 'Zend/Auth.php';
require_once 'Zend/Auth/Adapter/OpenId.php';
require_once 'Zend/Controller/Response/Abstract.php';
require_once 'Zend/Session.php';

// ZF is a monolith
Zend_Session::$_unitTestEnabled = true;

class ZfControllerResponseAdapter extends Zend_Controller_Response_Abstract {
  public function canSendHeaders($throw = false) {
    return true;
  }
  public function sendResponse() {
    $headers = $this->_headersRaw;
    foreach ($this->_headers as $header) {
      $headers[] = $header['name'] . ': ' . $header['value'];
    }
    throw new ZfThrowableResponse(
      $this->_httpResponseCode,
      implode("", $this->_body),
      $headers);
  }
}

class ZfThrowableResponse extends Exception {
  function __construct($status, $body, $headers) {
    $this->status = $status;
    $this->body = $body;
    $this->headers = $headers;
  }
  function status() {
    return $this->status;
  }
  function body() {
    return $this->body;
  }
  function headers() {
    return $this->headers;
  }
  function getRedirect() {
    foreach ($this->headers as $header) {
      if (preg_match('/^location: (.*)$/i', $header, $reg)) {
        return $reg[1];
      }
    }
  }
}

class SessionIdentityLoader implements k_IdentityLoader {
  function load(k_Context $context) {
    if ($context->session('identity')) {
      return $context->session('identity');
    }
    return new k_Anonymous();
  }
}

class NotAuthorizedComponent extends k_Component {
  function dispatch() {
    // redirect to login-page
    return new k_TemporaryRedirect($this->url('/login', array('continue' => $this->requestUri())));
  }
}