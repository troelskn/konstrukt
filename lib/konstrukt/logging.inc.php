<?php
/**
 * Collects dispatch trace and debug information to present a debug output.
 * It's a bunch of nasty, hard coded HTML, but it works and it's self-contained.
 */
class k_logging_WebDebugger implements k_DebugListener {
  protected $request_headers = array();
  protected $request_method = '';
  protected $route = array();
  protected $messages = array();
  protected $dumper;
  function __construct() {
    $this->dumper = new k_logging_HtmlDumper(new k_logging_XrayVision());
  }
  function logRequestStart(k_Context $context) {
    $this->request_method = $context->method();
    $this->request_headers = $context->header();
  }
  function logException(Exception $ex) {}
  function logDispatch($component, $name, $next) {
    $this->route[] = array('class-name' => get_class($component), 'name' => $name, 'next' => $next);
  }
  function log($mixed) {
    $stacktrace = debug_backtrace();
    $this->messages[] = array('file' => $stacktrace[0]['file'], 'line' => $stacktrace[0]['line'], 'dump' => $this->dumper->dump($mixed));
  }
  protected function renderRequest() {
    $html =
      '<div style="border:2px solid #848;margin:1em">' .
      '<div style="padding:.5em;color:white;font-weight:bold;background:#a6a">http-request</div>' .
      '<div style="padding:.5em;color:black;font-weight:bold;background:#fdf">http-method: ' . htmlspecialchars($this->request_method) . '</div>'
      ;
    foreach ($this->request_headers as $key => $value) {
      $html .= '<div style="padding:.5em;color:black;background:#fdf">' . htmlspecialchars($key) . ': ' . htmlspecialchars($value) . '</div>';
    }
    $html .= '</div>';
    return $html;
  }
  protected function renderDispatch($dispatch) {
    $cls = new ReflectionClass($dispatch['class-name']);
    $filename = $cls->getFilename();
    return
      '<div style="border:2px solid #00c;margin:1em">' .
      '<div style="padding:.5em;color:white;font-weight:bold;background:#44c">class-name: ' . htmlspecialchars($dispatch['class-name']) .
      '<div style="font-weight:normal">defined in: ' . htmlspecialchars($filename) . '</div>' . '</div>' .
      '<div style="padding:.5em;color:black;background:#cdf">name: ' . ($dispatch['name'] === null ? '<em>null</em>' : htmlspecialchars($dispatch['name'])) . '</div>' .
      '<div style="padding:.5em;color:black;background:#cdf">next: ' . ($dispatch['next'] === null ? '<em>null</em>' : htmlspecialchars($dispatch['next'])) . '</div>' .
      '</div>'
      ;
  }
  protected function renderResponse($response) {
    $html =
      '<div style="border:2px solid #848;margin:1em">' .
      '<div style="padding:.5em;color:white;font-weight:bold;background:#a6a">http-response</div>' .
      '<div style="padding:.5em;color:black;font-weight:bold;background:#fdf">http-status: ' . htmlspecialchars($response->status()) . '</div>' .
      '<div style="padding:.5em;color:black;font-weight:bold;background:#fdf">content-type: ' . htmlspecialchars($response->contentType()) . '</div>' .
      '<div style="padding:.5em;color:black;font-weight:bold;background:#fdf">charset: ' . htmlspecialchars($response->encoding()) . '</div>'
      ;
    foreach ($response->headers() as $key => $value) {
      if ($key === 'location') {
        $short_location = $value;
        if (strlen($short_location) > 50) {
          $short_location = substr($short_location, 0, 50) . ' (...)';
        }
        $html .= '<div style="padding:.5em;color:black;background:#fdf">' . htmlspecialchars($key) . ': <a href="' . htmlspecialchars($value) . '" title="' . htmlspecialchars($value) . '">' . htmlspecialchars($short_location) . '</a></div>';
      } else {
        $html .= '<div style="padding:.5em;color:black;background:#fdf">' . htmlspecialchars($key) . ': ' . htmlspecialchars($value) . '</div>';
      }
    }
    $html .= '</div>';
    return $html;
  }
  protected function renderMessages() {
    if (count($this->messages) === 0) {
      return "";
    }
    $html =
      '<div style="border:2px solid #888;margin:1em">' .
      '<div style="padding:.5em;color:white;font-weight:bold;background:#aaa">messages (' . count($this->messages) . ')</div>'
      ;
    foreach ($this->messages as $message) {
      $html .=
        '<div style="border:2px solid #f90;margin:1em">' .
        '<div style="padding:.5em;color:#f90;font-weight:bold;background:#fe0">' . htmlspecialchars($message['file']) . ' : ' . htmlspecialchars($message['line']) . '</div>' .
        '<div style="padding:.5em;color:black;background:#ffc">' . $message['dump'] . '</div>' .
        '</div>';
    }
    $html .= '</div>';
    return $html;
  }
  protected function render($response, $is_visible = true) {
    return
      '<div style="font:16px serif">' .
      '<div id="konstrukt-debug-handle" style="font-weight:bold;background:#090;border:2px solid #060;color:white;position:absolute;top:0;right:0;z-index:99" onclick="document.getElementById(\'konstrukt-debug-output\').style.display = \'block\';">' .
      'konstrukt-debug' .
      '</div>' .
      '<div id="konstrukt-debug-output" style="display:' . ($is_visible ? 'block' : 'none') . ';background:white;border:2px solid #060;position:absolute;top:0;right:0;z-index:100">' .
      '<div style="font-weight:bold;padding:.5em;background:#090;color:white" onclick="document.getElementById(\'konstrukt-debug-output\').style.display = \'none\';">' .
      'konstrukt-debug' .
      '</div>' .
      $this->renderRequest() .
      $this->renderResponse($response) .
      '<div style="border:2px solid #888;margin:1em">' .
      '<div style="padding:.5em;color:white;font-weight:bold;background:#aaa">route (' . count($this->route) . ')</div>' .
      implode("", array_map(array($this, 'renderDispatch'), $this->route)) .
      '</div>' .
      $this->renderMessages() .
      '</div>' .
      '</div>'
      ;
  }
  function decorate(k_HttpResponse $response) {
    if ($response->status() >= 300 && $response->status() < 400) {
      return new k_HttpResponse(200, $this->render($response, true));
    }
    if ($response->contentType() == 'text/html') {
      $html = $response->content();
      if (strpos($html, '</body>') === false) {
        $response->setContent($html . $this->render($response, false));
      } else {
        $response->setContent(str_replace('</body>', $this->render($response, false) . '</body>', $html));
      }
      return $response;
    }
  }
}

/**
 * Exports variable information, including private/protected variables and with recursion-protection.
 * Since this is built upon PHP serialization functionality, unserializable objects may cause trouble.
 */
class k_logging_XrayVision {
  protected $id;
  function export($object) {
    $this->id = 1;
    list($value, $input) = $this->parse(serialize($object));
    return $value;
  }
  protected function parse($input) {
    if (substr($input, 0, 2) === 'N;') {
      return array(array('type' => 'null', 'id' => $this->id++, 'value' => null), substr($input, 2));
    }
    $pos = strpos($input, ':');
    $type = substr($input, 0, $pos);
    $input = substr($input, $pos + 1);
    switch ($type) {
    case 's':
      return $this->s($input);
    case 'i':
      return $this->i($input);
    case 'd':
      return $this->d($input);
    case 'b':
      return $this->b($input);
    case 'O':
      return $this->o($input);
    case 'a':
      return $this->a($input);
    case 'r':
      return $this->r($input);
    }
    throw new Exception("Unhandled type '$type'");
  }
  protected function s($input) {
    $pos = strpos($input, ':');
    $length = substr($input, 0, $pos);
    $input = substr($input, $pos + 1);
    $value = substr($input, 1, $length);
    return array(array('type' => 'string', 'id' => $this->id++, 'value' => $value), substr($input, $length + 3));
  }
  protected function i($input) {
    $pos = strpos($input, ';');
    $value = (integer) substr($input, 0, $pos);
    return array(array('type' => 'integer', 'id' => $this->id++, 'value' => $value), substr($input, $pos + 1));
  }
  protected function d($input) {
    $pos = strpos($input, ';');
    $value = (float) substr($input, 0, $pos);
    return array(array('type' => 'float', 'id' => $this->id++, 'value' => $value), substr($input, $pos + 1));
  }
  protected function b($input) {
    $pos = strpos($input, ';');
    $value = substr($input, 0, $pos) === '1';
    return array(array('type' => 'boolean', 'id' => $this->id++, 'value' => $value), substr($input, $pos + 1));
  }
  protected function r($input) {
    $pos = strpos($input, ';');
    $value = (integer) substr($input, 0, $pos);
    return array(array('type' => 'recursion', 'id' => $this->id++, 'value' => $value), substr($input, $pos + 1));
  }
  protected function o($input) {
    $id = $this->id++;
    $pos = strpos($input, ':');
    $name_length = substr($input, 0, $pos);
    $input = substr($input, $pos + 1);
    $name = substr($input, 1, $name_length);
    $input = substr($input, $name_length + 3);
    $pos = strpos($input, ':');
    $length = (int) substr($input, 0, $pos);
    $input = substr($input, $pos + 2);
    $values = array();
    for ($ii=0; $ii < $length; $ii++) {
      list($key, $input) = $this->parse($input);
      $this->id--;
      list($value, $input) = $this->parse($input);
      if (substr($key['value'], 0, 3) === "\000*\000") {
        $values['protected:' . substr($key['value'], 3)] = $value;
      } elseif ($pos = strrpos($key['value'], "\000")) {
        $values['private:' . substr($key['value'], $pos + 1)] = $value;
      } else {
        $values[str_replace("\000", ':', $key['value'])] = $value;
      }
    }
    return array(
      array('type' => 'object', 'id' => $id, 'class' => $name, 'value' => $values),
      substr($input, 1));
  }
  protected function a($input) {
    $id = $this->id++;
    $pos = strpos($input, ':');
    $length = (int) substr($input, 0, $pos);
    $input = substr($input, $pos + 2);
    $values = array();
    for ($ii=0; $ii < $length; $ii++) {
      list($key, $input) = $this->parse($input);
      $this->id--;
      list($value, $input) = $this->parse($input);
      $values[$key['value']] = $value;
    }
    return array(
      array('type' => 'array', 'id' => $id, 'value' => $values),
      substr($input, 1));
  }
}

/**
 */
class k_logging_LogDebugger implements k_DebugListener {
  protected $file_handle;
  protected $dumper;
  /**
    * @param string
    * @return null
    */
  function __construct($filename) {
    $this->file_handle = fopen($filename, 'at');
    $this->dumper = new k_logging_SexpDumper(new k_logging_XrayVision());
    date_default_timezone_set(@date_default_timezone_get());
  }
  protected function indent($str) {
    $str = str_replace("\n", "\n  ", $str);
    if (substr($str, -3) === "\n  ") {
      $str = substr($str, 0, -3);
    }
    return $str;
  }
  function logRequestStart(k_Context $context) {
    $this->write(
      '(request' . "\n" .
      '  (time "' . date("Y-m-d H:i:s") . '")' . "\n" .
      '  (method "' . $context->method() . '")' . "\n" .
      '  (headers ' . $this->indent(($this->dumper->dump($context->header()))) . "))" . "\n");
  }
  function logException(Exception $ex) {
    $this->write("(exception\n" . $ex->__toString() . ")\n");
  }
  /**
    * @param test_CircularComponent
    * @param string
    * @param string
    * @return null
    */
  function logDispatch($component, $name, $next) {
    $this->write(
      $this->renderDispatch(
        array('class-name' => get_class($component), 'name' => $name, 'next' => $next)));
  }
  function log($mixed) {
    $stacktrace = debug_backtrace();
    $this->write(
      $this->renderMessage(
        array('file' => $stacktrace[0]['file'], 'line' => $stacktrace[0]['line'], 'dump' => $this->dumper->dump($mixed))));
  }
  function decorate(k_HttpResponse $response) {
    $this->write($this->renderResponse($response));
    return $response;
  }
  /**
    * @param string
    * @return null
    */
  protected function write($str) {
    fwrite($this->file_handle, $str);
  }
  /**
    * @param array
    * @return string
    */
  protected function renderDispatch($dispatch) {
    $cls = new ReflectionClass($dispatch['class-name']);
    $filename = $cls->getFilename();
    return
      '(dispatch' . "\n" .
      '  (class-name "' . $dispatch['class-name'] . '" (defined-in "' . $filename . '"))' . "\n" .
      '  (name ' . ($dispatch['name'] === null ? '*NULL*' : $dispatch['name']) . ')' . "\n" .
      '  (next ' . ($dispatch['next'] === null ? '*NULL*' : $dispatch['next']) . '))' . "\n"
      ;
  }
  protected function renderResponse($response) {
    $html =
      '(http-response' . "\n" .
      '  (http-status ' . $response->status() . ')' . "\n" .
      '  (content-type "' . $response->contentType() . '")' . "\n" .
      '  (charset "' . $response->encoding() . '")' . "\n" .
      '  (headers'
      ;
    foreach ($response->headers() as $key => $value) {
      $html .= "\n" . '    (' . $key . ' "' . $value . '")';
    }
    return $html . '))' . "\n";
  }
  protected function renderMessage($message) {
    return
      '(message "' . $message['file'] . '" ' . $message['line'] . ')' .
      "\n" .
      $message['dump'] .
      "\n"
      ;
  }
}

abstract class k_logging_DataDumper {
  protected $reflector;
  /**
    * @param k_logging_XrayVision
    * @return null
    */
  function __construct($reflector) {
    $this->reflector = $reflector;
  }
  function dump($mixed) {
    return $this->transform($this->reflector->export($mixed));
  }
  protected function transform($var) {
    switch ($var['type']) {
    case 'object':
      return $this->_object($var);
    case 'array':
      return $this->_array($var);
    case 'integer':
      return $this->_integer($var);
    case 'string':
      return $this->_string($var);
    case 'float':
      return $this->_float($var);
    case 'boolean':
      return $this->_boolean($var);
    case 'null':
      return $this->_null($var);
    case 'recursion':
      return $this->_recursion($var);
    }
    throw new Exception("Unknown type " . $var['type']);
  }
  abstract protected function _object($var);
  abstract protected function _array($var);
  abstract protected function _integer($i);
  abstract protected function _string($s);
  abstract protected function _float($f);
  abstract protected function _boolean($b);
  abstract protected function _null();
  abstract protected function _recursion($r);
}

/**
 * Generates a HTML formatted dump
 */
class k_logging_HtmlDumper extends k_logging_DataDumper {
  protected function _object($var) {
    $html =  '<table style="width:100%;border:2px solid #f90;border-spacing:0 0" id="konstrukt-debug-symbol-id-' . htmlspecialchars($var['id']) . '">' . "\n";
    $html .= '<caption style="font-weight:bold;text-align:left">object (' . htmlspecialchars($var['class']) . ' : #' . htmlspecialchars($var['id']) . ')</caption>' . "\n";
    $props = array();
    foreach ($var['value'] as $name => $child) {
      $props[] = '<tr><th style="background:#fe0;color:#f90;padding:.25em;text-align:right;vertical-align:top">' . htmlspecialchars($name) . '</th><td style="padding:.25em">' . $this->transform($child) . "</td></tr>";
    }
    if (count($props) > 0) {
      $html .=  '<tbody>' . "\n" . implode("\n", $props) . "\n" . '</tbody>' . "\n";
    }
    $html .=  '</table>' . "\n";
    return $html;
  }
  protected function _array($var) {
    $html =  '<table style="width:100%;border:2px solid #f90;border-spacing:0 0">' . "\n";
    $html .= '<caption style="font-weight:bold;text-align:left">array(' . count($var['value']) . ')</caption>';
    $props = array();
    foreach ($var['value'] as $name => $child) {
      $props[] = '<tr><th style="background:#fe0;color:#f90;padding:.25em;text-align:right;vertical-align:top">' . htmlspecialchars($name) . '</th><td style="padding:.25em">' . $this->transform($child) . "</td></tr>";
    }
    if (count($props) > 0) {
      $html .=  '<tbody>' . "\n" . implode("\n", $props) . "\n" . '</tbody>' . "\n";
    }
    $html .=  '</table>' . "\n";
    return $html;
  }
  protected function _integer($i) {
    return '<em>(integer)</em> ' . htmlspecialchars($i['value']);
  }
  protected function _string($s) {
    if (strlen($s['value']) > 50) {
      return '<span title="' . htmlspecialchars($s['value']) . '"><em>(string)</em> "' . htmlspecialchars(addslashes(substr($s['value'], 0, 50))) . ' ..."</span>';
    }
    return '<em>(string)</em> "' . htmlspecialchars(addslashes($s['value'])) . '"';
  }
  protected function _float($f) {
    return '<em>(float)</em> ' . htmlspecialchars(number_format($f['value']));
  }
  protected function _boolean($b) {
    return '<em>(boolean)</em> ' . ($b['value'] ? 'TRUE' : 'FALSE');
  }
  protected function _null() {
    return 'NULL';
  }
  protected function _recursion($r) {
    return '<em>recursion</em> <a href="#konstrukt-debug-symbol-id-' . htmlspecialchars($r['value']) . '">#' . htmlspecialchars($r['value']) . '</a>';
  }
}

/**
 * Generates dump formatted as s-expressions
 */
class k_logging_SexpDumper extends k_logging_DataDumper {
  protected function indent($str) {
    $str = str_replace("\n", "\n  ", $str);
    if (substr($str, -3) === "\n  ") {
      $str = substr($str, 0, -3);
    }
    return $str;
  }
  protected function _object($var) {
    $html = '(object "' . $var['class'] . '" (id ' . $var['id'] . ")\n";
    foreach ($var['value'] as $name => $child) {
      $html .= '  (' . $name . ' ' . $this->indent($this->transform($child)) . ')' . "\n";
    }
    return $html;
  }
  protected function _array($var) {
    $html = '(array (length ' . count($var['value']) . ")\n";
    foreach ($var['value'] as $name => $child) {
      $html .= '  (' . $name . ' ' . $this->indent($this->transform($child)) . ')' . "\n";
    }
    return $html;
  }
  protected function _integer($i) {
    return '(integer ' . $i['value'] . ')';
  }
  protected function _string($s) {
    return '(string "' . addslashes($s['value']) . '")';
  }
  protected function _float($f) {
    return '(float ' . number_format($f['value']) . ')';
  }
  protected function _boolean($b) {
    return '(boolean ' . ($b['value'] ? 'TRUE' : 'FALSE') . ')';
  }
  protected function _null() {
    return '(NULL)';
  }
  protected function _recursion($r) {
    return '(recursion ' . $r['value'] . ')';
  }
}