<?php
class k_UrlBuilder
{
  protected $base;

  function __construct($base) {
    $this->base = $base;
  }

  function url($href = "", $args = Array()) {
    $href = (string) $href;
    $hash = NULL;
    if (preg_match('/^(.*)#(.*)$/', $href, $matches)) {
      preg_match('/^(.*)#(.*)$/', $href, $matches);
      $href = $matches[1];
      $hash = $matches[2];
    }

    // re-parse path to normalize relative symbols
    $href = rtrim($href, "?");
    $href = trim($href, "/");
    $parts = Array();
    foreach (explode("/", $href) as $part) {
      if ($part == '..') {
        if (count($parts) == 0) {
          throw new Exception("Illegal path. Relative level extends below root.");
        }
        array_pop($parts);
      } else {
        $parts[] = $part;
      }
    }
    $href = implode("/", $parts);

    $href = $this->base . $href;
    if (!$args) {
      return $href;
    }
    if (preg_match("/(.*)\\?(.*)/", $href, $matches)) {
      $href = $matches[1];
      parse_str($matches[2], $parsed);
      $args = array_merge($parsed, $args);
    }
    $params = Array();
    foreach ($args as $key => $value) {
      if (!is_null($value)) {
        if (is_integer($key)) {
          $params[] = rawurlencode($value);
        } else {
          $params[] = rawurlencode($key) . "=" . rawurlencode($value);
        }
      }
    }
    if (count($params) > 0) {
      if (strpos($href, "?") === FALSE) {
        $href .= "?".implode("&", $params);
      } else {
        $href .= "&".implode("&", $params);
      }
    }
    if ($hash) {
      $href .= "#".$hash;
    }
    return $href;
  }
}