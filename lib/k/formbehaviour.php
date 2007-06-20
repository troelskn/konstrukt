<?php
/**
  * The k_FormBehaviour is a complex component, which provides the
  * expected behaviour of standard one-page forms. It has a number of hooks
  * and configurable features, and so should be useable for most
  * forms.
  *
  * When using the class, you will generally delegate full control to an
  * instance, by calling the forms execute() method from a controllers own
  * execute() method.
  *
  * The form can be configured by setting various public properties, of which
  * the [[$descriptors|#class-k_formbehaviour-property-descriptors]] is perhaps the most important.
  *
  * The form further connects with the controller by assuming it to provide a
  * public method validate(), which must return TRUE is the form is valid.
  *
  * Likewise, the controller must implement a public method validHandler(),
  * which will be called, if the form has been posted, and is valid.
  *
  * To simplify the implementation of validation, the form provides a [[validator|#class-k_validator]],
  * which can be recieved by calling [[getValidator()|#class-k_formbehaviour-method-getvalidator]].
  *
  * The form can further be customized by implementing the following public
  * methods in the controller, which will then be used instead of the default:
  * * [[getMemory()|#class-k_formbehaviour-method-getmemory]]
  * * [[getDefaultValues()|#class-k_formbehaviour-method-getdefaultvalues]]
  * * [[renderField()|#class-k_formbehaviour-method-renderfield]]
  *
  */
class k_FormBehaviour extends k_Component
{
  /**
    * @var k_Memory
    */
  protected $memory;
  /**
    * @var k_FieldCollection
    */
  protected $fields;
  /**
    * Name of the template to use for rendering the form.
    *
    * @var string
    */
  public $file_name;
  /**
    * An array of field descriptors.
    *
    * At the minimum, each descriptor must have a property 'name'.
    * Optionally, a property 'filters' can provide a number of callbacks to
    * filter incomming data. Also, a property 'default' may denote the
    * initial value of the field, although this can be overridden by
    * providing a custom implementation of [[getDefaultValues()|#class-k_formbehaviour-method-getdefaultvalues]]
    * @var array
    */
  public $descriptors = Array();
  /**
    * Controls whether the form values should be cleared, after
    * the validHandler() has been invoked.
    *
    * This will normally be TRUE for single page forms, and FALSE for multipage forms,
    * @var boolean
    */
  public $autoDestroy = TRUE;
  /**
    * QueryParam token, used for controlling persistence.
    *
    * This should generally be left untouched.
    * @var string
    */
  public $retainToken = "-retain";
  /**
    * Ascosiative array of HTML-attributes for the form element.
    *
    * @var array
    */
  public $properties = Array(
    "method" => "post",
    "action" => "#",
    "accept-charset" => "utf-8",
    "enctype" => "application/x-www-form-urlencoded",
  );

  function __construct($context, $file_name = "form.tpl.php") {
    parent::__construct($context);
    $this->file_name = $file_name;
    $this->properties["action"] = $this->url();
  }

  /**
    * Validates the syntax of descriptors.
    *
    * If the syntax is invalid, an exception is thrown.
    * @return void
    */
  function assertDescriptorsSyntax() {
    if (!is_array($this->descriptors)) {
      throw new Exception("Syntax error. Descriptors should be an array");
    }
    foreach ($this->descriptors as $descriptor) {
      if (!isset($descriptor['name'])) {
        throw new Exception("Syntax error. Descriptor should have a name");
      }
      if (isset($descriptor['filters']) && !is_array($descriptor['filters'])) {
        throw new Exception("Syntax error. 'filters' should be an array.");
      }
    }
  }

  /**
    * Creates a new validator for validating the forms values.
    *
    * @return k_Validator
    */
  function getValidator() {
    return new k_Validator($this->getMemory(), $this, $this->getMemory()->getFields());
  }

  /**
    * Callback to retrieve the memory object.
    *
    * The call is delegated to the context, if it provides a getMemory method;
    * Otherwise, a memory object is instantiated and stored in session.
    * @return array
    */
  protected function getMemory() {
    if (!$this->memory) {
      if (method_exists($this->context, 'getMemory')) {
        $this->memory = $this->context->getMemory();
      } else {
        $container =& $this->registry->session->get($this->url("", NULL));
        if (!isset($container["memory"])) {
          $container["memory"] = new k_Memory();
        }
        $this->memory = $container["memory"];
      }
    }
    return $this->memory;
  }

  /**
    * Callback to populate the memory with initial data.
    *
    * The call is delegated to the context, if it provides a getDefaultValues method;
    * Otherwise, the descriptors are scanned for a property 'default'
    * @return array
    */
  protected function getDefaultValues() {
    if (method_exists($this->context, 'getDefaultValues')) {
      return $this->context->getDefaultValues();
    }
    $values = Array();
    foreach ($this->descriptors as $descriptor) {
      if (array_key_exists('default', $descriptor)) {
        $values[$descriptor['name']] = $descriptor['default'];
      }
    }
    return $values;
  }

  /**
    * Callback from the template, to allow the controller to render the HTML for a field.
    *
    * The call is delegated to the context, if it provides a renderField method;
    * Otherwise, a <input type='text'/> is returned.
    * @return string
    */
  function renderField($field) {
    if (method_exists($this->context, 'renderField')) {
      return $this->context->renderField($field);
    }
    return "<input type='text' name='".htmlspecialchars($field->getName())."' id='".htmlspecialchars($field->getId())."' value='".htmlspecialchars($field->getValue())."' />";
  }

  /**
    * Returns the HTML-attributes for the form element.
    *
    * @return array
    */
  function getFormProperties($args = Array()) {
    $props = Array();
    foreach (array_merge($this->properties, $args) as $key => $value) {
      $props[] = "$key=\"".addslashes(htmlspecialchars($value))."\"";
    }
    return implode(" ", $props);
  }

  function execute() {
    $this->assertDescriptorsSyntax();
    $this->fields = new k_FieldCollection($this->getMemory(), $this, $this->descriptors);
    if ($this->retainToken && !isset($this->GET[$this->retainToken])) {
      $this->getMemory()->destroy();
    }
    if ($this->getMemory()->isNew()) {
      $this->fields->import($this->getDefaultValues());
      $this->getMemory()->setNotNew();
    }

    $method = $this->ENV['K_HTTP_METHOD'];
    if (!in_array($method, Array('GET', 'POST'))) {
      throw new k_http_Response(405);
    }
    return $this->{$method}();
  }

  protected function GET() {
    if (method_exists($this->context, 'viewHandler')) {
      $response = $this->context->viewHandler();
    } else {
      $response = $this->render($this->file_name);
    }
    $this->getMemory()->purgeMessages();
    return $response;
  }

  protected function POST() {
    foreach ($this->fields->neededDatasources() as $datasource) {
      if (isset($this->$datasource)) {
        $this->fields->import($this->$datasource, $datasource);
      } else {
        throw new Exception("Required datasource '$datasource' isn't available.");
      }
    }
    if ($this->context->validate($this->getMemory()->getFields())) {
      try {
        $response = $this->context->validHandler($this->getMemory()->getFields());
      } catch (k_http_Response $ex) {
        $response = $ex;
      }
      if ($this->autoDestroy) {
        $this->getMemory()->destroy();
      }
      if ($response instanceOf k_http_Response) {
        throw $response;
      }
      return $response;
    }
    if (isset($this->retainToken)) {
      throw new k_http_Redirect($this->url("", Array($this->retainToken)));
    } else {
      throw new k_http_Redirect($this->url());
    }
  }
}