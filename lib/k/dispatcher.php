<?php
/**
  * The dispatcher is the top level controller in the hierarchy.
  * It provides a common place for every thing global to the request, such as
  * loading the users identity.
  *
  * Rather than calling handleRequest directly, users should call dispatch,
  * which executes the hierarchy and outputs the response to the browser.
  */
class k_Dispatcher extends k_Controller
{
  /**
    * @var callback
    */
  public $userGateway;
  /**
    * When TRUE, debugging can be enabled by setting the cookie named 'DEBUG'.
    *
    * @see k_Debugger
    * @var boolean
    */
  public $debug = FALSE;

  function __construct(k_iContext $context = NULL) {
    parent::__construct(is_null($context) ? new k_http_Request() : $context);
    $this->registry->set('document', new k_Document());
    // @see k_Debugger
    if ($this->debug && isset($_COOKIE['DEBUG'])) {
      $this->registry->set('debugger', new k_Debugger());
      setcookie("DEBUG", 0, time() - 3600, "/");
    }
    $this->registry->registerAlias('identity', ':identity');
    $this->registry->registerConstructor(
      ':identity',
      Array($this, 'loadIdentity')
    );
    $this->userGateway = Array($this, 'loadUser');
  }

  /**
    * Main entrypoint of the application.
    *
    * Handles request, and outputs the result to the client.
    * @return  void
    */
  function dispatch() {
    try {
      $response = new k_http_Response(200, $this->handleRequest());
    } catch (k_http_Response $ex) {
      $response = $ex;
    }
    if (isset($this->debugger)) {
      $this->debugger->outputResponse($response);
    } else {
      $response->out();
    }
  }

  function loadIdentity() {
    return call_user_func(
      $this->userGateway,
      isset($this->ENV['PHP_AUTH_USER']) ? $this->ENV['PHP_AUTH_USER'] : NULL,
      isset($this->ENV['PHP_AUTH_PW']) ? $this->ENV['PHP_AUTH_PW'] : NULL
    );
  }

  /**
    * @param   string   $username
    * @param   string   $password
    * @return  User object instance.
    */
  function loadUser($username, $password) {
    return new k_Anonymous();
  }

  /**
    * This is for BC. Use $this->url("/xxx") instead of $this->top("xxx")
    *
    * @deprecated
    */
  function top($href = "", $args = Array()) {
    return $this->url($href, $args);
  }

  function handleRequest() {
    if (!isset($this->document->template)) {
      return parent::handleRequest();
    }
    return $this->render($this->document->template, Array(
      'content' => parent::handleRequest(),
      'encoding' => $this->document->encoding,
      'title' => $this->document->title,
      'scripts' => $this->document->scripts,
      'styles' => $this->document->styles,
    ));
  }

  function url($href = "", $args = Array()) {
    $href = parent::url(ltrim($href, "/"), $args);
    $parts = Array();
    foreach (explode("/", $href) as $part) {
      if ($part == ".." && count($parts) > 0) {
        array_pop($parts); // one up
      } else if ($part != ".") {
        $parts[] = $part; // skip
      }
    }
    return implode("/", $parts);
  }
}
