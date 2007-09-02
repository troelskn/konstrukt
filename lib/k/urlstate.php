<?php
class k_UrlState implements k_iStateContainer
{
  protected $source;
  protected $namespace;
  protected $separator = '-';

  protected $values = Array();
  protected $contextStateParams = Array();

  function __construct(k_iStateContainer $source, $namespace = '') {
    $this->source = $source;
    $this->namespace = $namespace;
  }

  /**
   * Initializes the state-container with persistent values.
   * Takes array of name => default-value
   */
  function initialize($params = Array()) {
    foreach ($params as $key => $value) {
      $this->registerPersistent($key, $value);
    }
  }

  /**
   * Initializes a single persistent value.
   * The optional default value will be used only if the source doesn't already have a value.
   */
  function registerPersistent($key, $default_value = NULL) {
    $this->contextStateParams[] = $key;
    $name = $this->canonizeName($key);
    $value = $this->source->get($name);
    if (!is_null($value)) {
      $this->values[$key] = $value;
      $this->source->set($name, $value);
    } else if (!is_null($default_value)) {
      $this->values[$key] = $default_value;
      $this->source->set($name, $default_value);
    }
  }

  protected function canonizeName($key) {
    if ($this->namespace) {
      return $this->namespace . $this->separator . $key;
    }
    return $key;
  }

  function set($key, $value) {
    if (in_array($key, $this->contextStateParams)) {
      $this->source->set($this->canonizeName($key), $value);
    }
    $this->values[$key] = $value;
  }

  function get($key) {
    if (in_array($key, $this->contextStateParams)) {
      $key = $this->canonizeName($key);
    }
    if (isset($this->values[$key])) {
      return $this->values[$key];
    }
    return $this->source->get($key);
  }

  function export($args = Array()) {
    $export = Array();
    foreach (array_merge($this->values, $args) as $key => $value) {
      if (in_array($key, $this->contextStateParams)) {
        $export[$this->canonizeName($key)] = $value;
      } else {
        $export[$key] = $value;
      }
    }
    return $export;
  }
}
