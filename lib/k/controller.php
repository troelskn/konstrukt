<?php
/**
  * This is the baseclass for all controllers.
  *
  * In Konstrukt, controllers are closely related to URI's, which means that the
  * responsibility for generating URL's is with the controller. The method
  * [[url()|#class-k_controller-method-url]] generates a URL pointing to
  * the controller.
  *
  * The incomming (http) request is resolved by a chain of controllers, where
  * each has the responsibility to delegate control to each other. This is
  * unlike most other frameworks in that there isn't an up-front frontcontroller,
  * which dispatches to a single final handler. Each controller can delegate
  * to another controller or it can handle the request on itself. This design
  * gives a greater flexibility, while ensuring structure in your application.
  *
  * Flow
  * ----
  * A controller will always have a [[context|#class-k_controller-property-context]],
  * which will normally be another controller or a [[k_http_Request|#class-k_http_request]].
  * If the controller forwards to a child-controller, it will become context for this.
  *
  * A controllers execution begins with [[handleRequest()|#class-k_controller-method-handlerequest]].
  * This method, determines if the flow should be forwarded to a child-controller
  * or if this is the final handler. This is determined on ground of the context's
  * URI-subspace. If the subspace indicates that control be forwarded, the method
  * [[forward()|#class-k_controller-method-forward]] is called, with the
  * name, identifying the delegate. If the controller doesn't override the default
  * implementation of forward(), the name is resolved to a classname, using [[$map|#class-k_controller-property-map]]
  *
  * If the controller is the final handler, a method corresponding to the HTTP
  * request method will be called. The most common is [[GET()|#class-k_controller-method-get]]
  * Alternatively, if the controller needs to act on *every* request-type, you can override
  * [[execute()|#class-k_controller-method-execute]] instead. This is however not recommended
  * in general.
  */
class k_Controller extends k_Component implements k_iContext
{
  /**
    * An hashmap of URI-name => classname of sub-controllers.
    *
    * An ArrayAccess can be used
    * @var array
    */
  public $map = Array();
  /**
    * The URI-name of this controller.
    *
    * This value is assigned in the constructor and shouldn't be changed at runtime.
    * The value is the name, by which the context chose to forward to this controller.
    * @var string
    */
  public $name = "";
  /**
    * The URI-subspace for this controller.
    *
    * This value is normally assigned from the creating context.
    * @var string
    */
  protected $subspace = "";
  /**
    * The name of the forwarded sub-controller if any.
    *
    * This is a subset of property 'subspace'. It is equal to the sub-controllers property 'name'.
    * This value is assigned when and if the controller forwards to a child-controller.
    * @var string
    */
  protected $forward;
  /**
    * @var array
    */
  protected $subController = Array();

  /**
    * @param   k_iContext         $context   The creating context
    * @param   string             $name      URI-name relative to the parent
    * @return  void
    */
  function __construct(k_iContext $context, $name = "", $urlNamespace = "") {
    parent::__construct($context, $urlNamespace);
    $this->name = $name;
    if (isset($this->debugger)) {
      $this->debugger->logController($this);
    }
  }

  function getSubspace() {
    return $this->subspace;
  }

  function url($href = "", $args = Array()) {
    $href = (string) $href;
    $hash = NULL;
    if (isset($this->name) && $this->name != "") {
      if (preg_match('/^(.*)#(.*)$/', $href, $matches)) {
        $href = $matches[1];
        $hash = $matches[2];
      }
      if ($href == "") {
        $href = $this->name;
      } else if (!preg_match('~^/(.*)$~', $href)) {
        $href = $this->name."/".$href;
      }
      if ($hash) {
        $href .= "#".$hash;
      }
    }
    return parent::url($href, $args);
  }

  /**
    * This is for BC. Use $this->url("/xxx") instead of $this->top("xxx")
    *
    * @deprecated
    */
  function top($href = "", $args = Array()) {
    return $this->context->top($href, $args);
  }

  /**
    * Entrypoint for execution.
    *
    * You shouldn't need to call this directly.
    * The method determines if the controller is final handler or if it should forward,
    * and then either calls [[forward()|#class-k_controller-method-forward]] or
    * [[execute()|#class-k_controller-method-execute]].
    * @return string
    * @throws k_http_Response
    */
  function handleRequest() {
    // @todo This could just be moved into getSubspace()
    // determine this controllers subspace from name + context->subspace
    $this->subspace = preg_replace('~^'.(preg_quote($this->name, "~")).'/?~', "", $this->context->getSubspace());
    $next = $this->findNext();
    if (!is_null($next)) {
      return $this->forward($next);
    }
    // execute this controller, since we didn't forward
    $response = $this->execute();
    return $response;
  }

  /**
    * Determine the name of the next controller, if any.
    * As long as your URL's follow the standard / delimited style, you shouldn't need to override this method.
    * If you find that you do, you might want to reconsider why you URL's are structured as they are.
    */
  protected function findNext() {
    if (preg_match('~^([^/]+)/?(.*)$~', $this->subspace, $matches)) {
      return $matches[1];
    }
  }

  /**
    * Indirects execution to a subcontroller.
    * You shouldn't need to override this method. If you want to change the mapping logic, override
    * createSubController instead.
    * Note that forward was split up, so code which overrides forward() are likely candidates to
    * be moved to createSubController, with small adjustments.
    *
    * @param  string    $name    URI-name of the subcontroller
    * @return string
    * @throws k_http_Response
    */
  protected function forward($name) {
    $next = $this->getSubController($name);
    $this->forward = $name;
    return $next->handleRequest();
  }

  /**
   * Returns a subcontroller.
   * Acts as a registry, so the same subcontroller is only created once.
   * @see createSubController
   */
  protected function getSubController($name) {
    if (!isset($this->subController[$name])) {
      $this->subController[$name] = $this->createSubController($name);
    }
    return $this->subController[$name];
  }

  /**
   * Creates a subcontroller.
   * By default, $map is used to map name to classname, but you can override this
   * method, to supply specific mapping logic.
   */
  protected function createSubController($name) {
    if (!isset($this->map[$name])) {
      throw new k_http_Response(404);
    }
    $classname = $this->map[$name];
    if (!class_exists($classname)) {
      throw new k_http_Response(500);
    }
    return new $classname($this, $name);
  }

  /**
    * This method is called if this controller is the final handler.
    *
    * Per default, it delegates to [[GET()|#class-k_controller-method-get]],
    * [[POST()|#class-k_controller-method-post]] etc., so you should only override
    * this method, in special cases. Otherwise
    * [[GET()|#class-k_controller-method-get]] is most likely what you
    * are looking for.
    * @return string
    * @throws k_http_Response
    */
  function execute() {
    $method = @$this->ENV['K_HTTP_METHOD'];
    if (!in_array($method, Array('HEAD','GET','POST','PUT','DELETE'))) {
      throw new k_http_Response(405);
    }
    return $this->{$method}();
  }

  /**
    * GET handler.
    *
    * This is where you should implement the code for dealing with
    * GET-type requests.
    * @return string
    * @throws k_http_Response
    */
  function GET() {
    throw new k_http_Response(501);
  }

  /**
    * POST handler.
    *
    * This is where you should implement the code for dealing with
    * POST-type requests.
    * @return string
    * @throws k_http_Response
    */
  function POST() {
    throw new k_http_Response(501);
  }

  /**
    * HEAD handler.
    *
    * This is where you should implement the code for dealing with
    * HEAD-type requests.
    * @return string
    * @throws k_http_Response
    */
  function HEAD() {
    throw new k_http_Response(501);
  }

  /**
    * PUT handler.
    *
    * This is where you should implement the code for dealing with
    * PUT-type requests.
    * @return string
    * @throws k_http_Response
    */
  function PUT() {
    throw new k_http_Response(501);
  }

  /**
    * DELETE handler.
    *
    * This is where you should implement the code for dealing with
    * DELETE-type requests.
    * @return string
    * @throws k_http_Response
    */
  function DELETE() {
    throw new k_http_Response(501);
  }
}
