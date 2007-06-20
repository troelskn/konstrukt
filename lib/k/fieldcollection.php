<?php
/**
  * A collection of [[fields|#class-k_field]].
  *
  * The k_FieldCollection is used by [[k_FieldBehaviour|#class-k_fieldbehaviour]].
  */
class k_FieldCollection implements IteratorAggregate
{
  protected $fields = Array();
  protected $memory;

  function __construct($memory, $renderer, $descriptors) {
    $this->fields = Array();
    $this->memory = $memory;
    foreach ($descriptors as $descriptor) {
      $this->fields[$descriptor['name']] = new k_Field($renderer, $this->memory, $descriptor);
    }
  }

  function __get($name) {
    return $this->fields[$name];
  }

  function getIterator() {
    return new ArrayIterator($this->fields);
  }

  function import($input, $datasource = NULL) {
    if ($input instanceOf ArrayObject) {
      $input = $input->getArrayCopy();
    }
    foreach ($this->fields as $name => $field) {
      if (is_null($datasource) || $datasource == $field->getDatasource()) {
        $field->import($input);
      }
    }
  }

  function neededDatasources() {
    $datasources = Array();
    foreach ($this->fields as $field) {
      $datasources[] = $field->getDatasource();
    }
    return array_unique($datasources);
  }

  function render($name) {
    return $this->fields[$name]->render();
  }

  function getId($name) {
    return $this->fields[$name]->getId();
  }

  function getMessages() {
    return $this->memory->getMessages();
  }

  /**
    * Returns a hash of fields of a particular fieldset.
    *
    * @return array
    */
  function getByFieldset($fieldset) {
    $fields = Array();
    foreach ($this->fields as $name => $field) {
      if ($field->getFieldset() == $fieldset) {
        $fields[$name] = $field;
      }
    }
    return $fields;
  }

  /**
    * Returns a hash of fields, exclusive those explicitly named.
    *
    * You can either pass an array of names to exclude or pass each field to exclude as string arguments.
    * @return array
    */
  function getExclusive($name /* [, $name2 [...]]*/) {
    $names = is_array($name) ? $name : func_get_args();
    $fields = Array();
    foreach ($this->fields as $name => $field) {
      if (!in_array($name, $names)) {
        $fields[$name] = $field;
      }
    }
    return $fields;
  }
}