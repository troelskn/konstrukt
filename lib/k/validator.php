<?php
/**
  * A helper class for validating a [[form|#class-k_formbehaviour]].
  *
  * In addition to providing a collection of standard validation methods,
  * it provides a simple interface for writing more complex validation of
  * forms.
  */
class k_Validator
{
  protected $logger;
  protected $translator;
  protected $values;
  protected $valid;

  /**
    * @param   $logger       mixed   Either an instance of k_Memory or a callback
    * @param   $translator   mixed   Either an instance of k_Component or a callback
    */
  function __construct($logger, $translator, $values, $valid = TRUE) {
    if (is_object($logger)) {
      $this->logger = Array($logger, 'logFail');
    } else {
      $this->logger = $logger;
    }
    if (is_object($translator)) {
      $this->translator = Array($translator, '__');
    } else {
      $this->translator = $translator;
    }
    $this->values = $values;
    $this->valid = $valid;
  }

  function isValid() {
    return $this->valid;
  }

  function fail($message) {
    return $this->failField("", $message);
  }

  function failField($field, $message) {
    call_user_func($this->logger, $field, $message);
    $this->valid = FALSE;
    return FALSE;
  }

  function assertRequired($name, $message = "%s is required") {
    if (!isset($this->values[$name]) || $this->values[$name] == "") {
      $this->failField($name,
        sprintf($message,
          call_user_func($this->translator, $name)
        )
      );
      return FALSE;
    }
    return TRUE;
  }

  function assertRegex($name, $regex, $message = "%s doesn't match") {
    if (!preg_match($regex, isset($this->values[$name]) ? $this->values[$name] : "")) {
      $this->failField($name,
        sprintf($message,
          call_user_func($this->translator, $name)
        )
      );
      return FALSE;
    }
    return TRUE;
  }

  function assertEmail($name, $message = "%s is not a valid email-address") {
    return $this->assertRegex($name, '/^[a-z0-9]+([-+.][a-z0-9]+)*@[a-z0-9]+([-.][a-z0-9]+)*\.[a-z0-9]+([-.][a-z0-9]+)*$/', $message);
  }

  function assertNumeric($name, $message = "%s must be numeric") {
    if (!isset($this->values[$name]) || !is_numeric($this->values[$name])) {
      $this->failField($name,
        sprintf($message,
          call_user_func($this->translator, $name)
        )
      );
      return FALSE;
    }
    return TRUE;
  }

  function assertWithin($name, $options = Array(), $message = "%s isn't an valid option") {
    if (!in_array(isset($this->values[$name]) ? $this->values[$name] : "", $options)) {
      $this->failField($name,
        sprintf($message,
          call_user_func($this->translator, $name)
        )
      );
      return FALSE;
    }
    return TRUE;
  }

  function assertEqual($name, $name2, $message = "%s must be equal to %s") {
    if (!isset($this->values[$name]) || !isset($this->values[$name2]) || $this->values[$name] != $this->values[$name2]) {
      $this->failField($name2,
        sprintf($message,
          call_user_func($this->translator, $name),
          call_user_func($this->translator, $name2)
        )
      );
      return FALSE;
    }
    return TRUE;
  }
}