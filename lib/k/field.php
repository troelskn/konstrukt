<?php
/**
  * Encapsulates a single form field.
  *
  * This object is mostly internal to the [[k_FormBehaviour|#class-k_formbehaviour]] class,
  * but is also exposed to the template, when rendering a form.
  */
class k_Field
{
  protected $renderer;
  protected $memory;

  protected $name;
  protected $id;
  protected $fieldset;
  protected $filters;
  protected $value;
  protected $datasource;

  function __construct($renderer, $memory, $descriptor) {
    $this->renderer = $renderer;
    $this->memory = $memory;
    $this->name = $descriptor['name'];
    $this->id = isset($descriptor['id']) ? $descriptor['id'] : uniqid("autoid-");
    $this->fieldset = isset($descriptor['fieldset']) ? $descriptor['fieldset'] : NULL;
    $this->filters = isset($descriptor['filters']) ? $descriptor['filters'] : Array();
    $this->datasource = strtoupper(isset($descriptor['datasource']) ? $descriptor['datasource'] : 'POST');
  }

  function __get($property) {
    if (method_exists($this, "get".$property)) {
      return $this->{"get".$property}();
    }
  }

  function getDatasource() {
    return $this->datasource;
  }

  function getName() {
    return $this->name;
  }

  function getId() {
    return $this->id;
  }

  function getFieldset() {
    return $this->fieldset;
  }

  function getValue() {
    return $this->memory->get($this->getName());
  }

  /**
    * Returns any validation errors from previous form submit.
    *
    * @return array
    */
  function getMessages() {
    return $this->memory->getMessages($this->getName());
  }

  /**
    * Calls back to the owning controller, to let it render the fields HTML representation.
    *
    * @return string
    */
  function render() {
    return $this->renderer->renderField($this);
  }

  function __toString() {
    return $this->render();
  }

  function import($input = Array()) {
    if (isset($input[$this->getName()])) {
      $value = $input[$this->getName()];
    } else {
      $value = NULL;
    }
    foreach ($this->filters as $filter) {
      $value = call_user_func($filter, $value);
    }
    $this->memory->set($this->getName(), $value);
  }
}
