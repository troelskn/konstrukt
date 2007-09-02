<?php
/**
 * A stateles state container. Used at the top level, for reading from GET.
 */
class k_UrlStateSource implements k_iStateContainer
{
  function __construct(k_iContext $context) {
    $this->context = $context;
  }

  function set($key, $value) {
    // ignore
  }

  function get($key) {
    $params = $this->context->getRegistry()->get('GET');
    return isset($params[$key]) ? $params[$key] : NULL;
  }

  function export() {
    return Array();
  }
}
