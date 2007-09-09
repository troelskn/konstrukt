<?php
/**
 * Utility for manipulating URL's at the string level.
 */
class k_UrlBuilder
{
  protected $normalize;
  protected $base;

  function __construct($base, $normalize = TRUE) {
    $this->base = $base;
    $this->normalize = $normalize;
  }

  protected function normalizePath($path) {
    $path = trim($path, "/");
    $parts = Array();
    foreach (explode("/", $path) as $part) {
      $part = urldecode($part);
      if ($part == '..') {
        if (count($parts) == 0) {
          throw new Exception("Illegal path. Relative level extends below root.");
        }
        array_pop($parts);
      } else {
        $parts[] = urlencode($part);
      }
    }
    return implode('/', $parts);
  }

  protected function parseUrl($href = '') {
    $hash = NULL;
    $query_string = NULL;
    if (preg_match('/^(.*)#(.*)$/', $href, $matches)) {
      $href = $matches[1];
      $hash = $matches[2];
    }
    if (preg_match('/^(.*)?(.*)$/', $href, $matches)) {
      $href = $matches[1];
      $query_string = $matches[2];
    }
    $params = Array();
    parse_str($query_string, $params);
    return Array($href, $params, $hash);
  }

  protected function compileQueryString($args = Array()) {
    if (count($args) == 0) {
      return '';
    }
    $params = Array();
    foreach ($args as $key => $value) {
      if (!is_null($value)) {
        if (is_integer($key)) {
          $params[] = rawurlencode($value);
        } else {
          $params[] = rawurlencode($key) . '=' . rawurlencode($value);
        }
      }
    }
    return '?' . implode('&', $params);
  }

  function url($href = '', $args = Array()) {
    list($href, $params, $hash) = $this->parseUrl((string) $href);
    if (!is_array($args)) {
      $args = Array();
    }
    $args = array_merge($params, $args);
    $href = $this->base . ($this->normalize ? $this->normalizePath($href) : $href);
    $href .= $this->compileQueryString($args);
    if ($hash) {
      $href .= '#' . $hash;
    }
    return $href;
  }
}
