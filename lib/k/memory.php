<?php
/**
  * The memory object is essentially a wrapper over the state of a [[form|#class-k_formbehaviour]].
  *
  * In addition to the values of the form, the memory also holds error-messages
  * from the previous validation, of any.
  */
class k_Memory
{
  /**
    * @var boolean
    */
  protected $state = FALSE;
  /**
    * @var array
    */
  protected $data = Array();
  /**
    * @var array
    */
  protected $messages = Array();
  /**
    * If volatile fields is set, the form values will not be
    * persisted -- only error messages will.
    *
    * This is useful for forms with confidential data, such as
    * creditcard information etc.
    * @var boolean
    */
  protected $volatileFields = FALSE;
  /**
    * @param boolean $volatileFields Set to TRUE to get volatile fields. (State isn't persisted)
    */
  function __construct($volatileFields = FALSE) {
    $this->volatileFields = $volatileFields;
  }

  function __sleep() {
    $properties = Array();
    $class = new ReflectionClass($this);
    foreach ($class->getProperties() as $property) {
       if (!$this->volatileFields || ($property->getName() != "data")) {
        $properties[] = $property->getName();
       }
    }
    return $properties;
  }

  function destroy() {
    $this->state = FALSE;
    $this->data = Array();
    $this->purgeMessages();
  }

  function isNew() {
    return !$this->state;
  }

  function setNotNew() {
    $this->state = TRUE;
  }

  function getFields() {
    return $this->data;
  }

  function set($key, $value) {
    $this->data[$key] = $value;
  }

  function get($key) {
    return isset($this->data[$key]) ? $this->data[$key] : NULL;
  }

  /**
    * Flushes all error-messages.
    */
  function purgeMessages() {
    $messages = $this->messages;
    $this->messages = Array();
    return $messages;
  }

  function getMessages($key = "") {
    return isset($this->messages[$key]) ? $this->messages[$key] : Array();
  }

  function exportMessages() {
    return $this->messages;
  }

  /**
    * Logs an error message on a field.
    *
    * Used by validation.
    */
  function logFail($key, $message) {
    if (!isset($this->messages[$key])) {
      $this->messages[$key] = Array();
    }
    $this->messages[$key][] = $message;
  }
}