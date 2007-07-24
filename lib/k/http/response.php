<?php
class k_http_Response extends Exception
{
  public $status = 200;
  public $content = "";
  public $encoding = "UTF-8";
  public $contentType = "text/html";
  public $protocol = "HTTP/1.1";
  public $headers = Array();

  public $outputEncoder = 'utf8_encode';

  function __construct($status = 200, $content = "") {
    $this->status = $status;
    if (isset($_SERVER['SERVER_PROTOCOL'])) {
      $this->protocol = $_SERVER['SERVER_PROTOCOL'];
    }
    $this->content = $content;
  }

  function __toString() {
    throw new Exception("k_http_Response to String conversion");
  }

  function setHeader($key, $value) {
    // normalize headers ... not really needed
    for ($tmp = explode("-", $key), $i=0;$i<count($tmp);$i++) {
      $tmp[$i] = ucfirst($tmp[$i]);
    }
    $key = implode("-", $tmp);
    if ($key == 'Content-Type') {
      if (preg_match('/^(.*);\w*charset\w*=\w*(.*)/', $value, $matches)) {
        $this->contentType = $matches[1];
        $this->encoding = $matches[2];
      } else {
        $this->contentType = $value;
      }
    } else {
      $this->headers[$key] = $value;
    }
  }

  function setStatus($status) {
    $this->status = $status;
  }

  function setContentType($contentType) {
    $this->contentType = $contentType;
  }

  function setEncoding($encoding) {
    $this->encoding = $encoding;
    if (strtoupper($this->encoding) == 'UTF-8') {
      $this->outputEncoder = 'utf8_encode';
    } else {
      $this->outputEncoder = NULL;
    }
  }

  function appendContent($content) {
    $this->content .= $content;
  }

  function setContent($content) {
    $this->content = $content;
  }

  protected function sendStatus() {
    switch ($this->status) {
      case 400 :  $statusmsg = "Bad Request";
            break;
      case 401 :  $statusmsg = "Unauthorized";
            break;
      case 403 :  $statusmsg = "Forbidden";
            break;
      case 404 :  $statusmsg = "Not Found";
            break;
      case 405 :  $statusmsg = "Method Not Allowed";
            break;
      case 406 :  $statusmsg = "Not Acceptable";
            break;
      case 410 :  $statusmsg = "Gone";
            break;
      case 412 :  $statusmsg = "Precondition Failed";
            break;
      case 500 :  $statusmsg = "Internal Server Error";
            break;
      case 501 :  $statusmsg = "Not Implemented";
            break;
      case 502 :  $statusmsg = "Bad Gateway";
            break;
      case 503 :  $statusmsg = "Service Unavailable";
            break;
      case 504 :  $statusmsg = "Gateway Timeout";
            break;
      default :  $statusmsg = "";
    }
    header($this->protocol." " . $this->status . ($statusmsg ? " " . $statusmsg : ""));
  }

  protected function sendHeaders() {
    if (isset($this->contentType)) {
      if (isset($this->encoding)) {
        header('Content-Type: '.$this->contentType."; charset=".$this->encoding);
      } else {
        header('Content-Type: '.$this->contentType);
      }
    }
    foreach ($this->headers as $key => $value) {
      if (!is_null($value)) {
        header($key . ": " . $value);
      }
    }
  }

  protected function sendBody() {
    if ($this->outputEncoder) {
      echo call_user_func($this->outputEncoder, $this->content);
    } else {
      echo $this->content;
    }
  }

  function out() {
    if (session_id()) {
      session_write_close();
    }
    $this->sendStatus();
    if ($this->status >= 400) {
      return;
    }
    $this->sendHeaders();
    $this->sendBody();
  }
}