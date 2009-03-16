<?php
class test_CircularComponent extends k_Component {
  function map($name) {
    return 'test_CircularComponent';
  }
  function execute() {
    return "Executing\n";
  }
  function dispatch() {
    return "Dispatching:\n  name: '" . $this->name() . "'\n  next: '" . $this->next() . "'\n  subtype: '" . $this->subtype() . "'\n  url: '" . $this->url() . "'\n"
      . parent::dispatch();
  }
}

class test_ExposedComponent extends k_Component {
  function getUrlState() {
    return $this->url_state;
  }
}

class k_adapter_DummyOutputAccess implements k_adapter_OutputAccess {
  public $http_response_code = 200;
  public $headers = array();
  public $body = "";
  public $session_ended = false;
  function header($string, $replace = true, $http_response_code = null) {
    $this->headers[] = array($string, $replace);
    if ($http_response_code !== null) {
      $this->http_response_code = $http_response_code;
    }
  }
  function write($bytes) {
    $this->body .= $bytes;
  }
  function endSession() {
    $this->session_ended = true;
  }
}

class k_TestDebugListener implements k_DebugListener {
	public $request_start = null;
  public $exceptions = array();
  public $dump = array();
  public $route = array();
	function logRequestStart(k_Context $context) {
		$this->request_start = $context;
	}
  function logException(Exception $ex) {
    $this->exceptions[] = $ex;
  }
  function logDispatch($component, $name, $next) {
    $this->route[] = $name;
  }
  function log($mixed) {
    $this->dump[] = $mixed;
  }
  function decorate(k_HttpResponse $response) {
    return $response;
  }
}

// from: http://www.php.net/stream_wrapper_register
// stream_wrapper_register("var", "VariableStream") or die("Failed to register protocol");
// $fp = fopen("var://myvar", "r+");
class VariableStream {
  protected $position;
  protected $varname;

  function stream_open($path, $mode, $options, &$opened_path) {
    $url = parse_url($path);
    $this->varname = $url["host"];
    $this->position = 0;

    return true;
  }

  function stream_read($count) {
    $ret = substr($GLOBALS[$this->varname], $this->position, $count);
    $this->position += strlen($ret);
    return $ret;
  }

  function stream_write($data) {
    $left = substr($GLOBALS[$this->varname], 0, $this->position);
    $right = substr($GLOBALS[$this->varname], $this->position + strlen($data));
    $GLOBALS[$this->varname] = $left . $data . $right;
    $this->position += strlen($data);
    return strlen($data);
  }

  function stream_tell() {
    return $this->position;
  }

  function stream_eof() {
    return $this->position >= strlen($GLOBALS[$this->varname]);
  }

  function stream_seek($offset, $whence) {
    switch ($whence) {
    case SEEK_SET:
      if ($offset < strlen($GLOBALS[$this->varname]) && $offset >= 0) {
        $this->position = $offset;
        return true;
      } else {
        return false;
      }
      break;

    case SEEK_CUR:
      if ($offset >= 0) {
        $this->position += $offset;
        return true;
      } else {
        return false;
      }
      break;

    case SEEK_END:
      if (strlen($GLOBALS[$this->varname]) + $offset >= 0) {
        $this->position = strlen($GLOBALS[$this->varname]) + $offset;
        return true;
      } else {
        return false;
      }
      break;

    default:
      return false;
    }
  }
}
