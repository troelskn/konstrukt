<?php
/**
  * Displays debug information about the request.
  *
  * By default, the debugger is created by the dispatcher, if the cookie 'DEBUG' is set, and debugging is allowed.
  *
  * You can use the following bookmarklet to switch debug output on for a request:
  *
  * ::raw
  * javascript:(function (document) { var COOKIE_NAME = %22DEBUG%22; var isset = function () {var nameEQ = COOKIE_NAME + %22=1%22;var ca = document.cookie.split(%22;%22);for (i = 0; i < ca.length; i++) {var c = ca[i];while (c.charAt(0) == %22 %22) {c = c.substring(1, c.length);}if (c.indexOf(nameEQ) == 0) {return true;}}return false;}; var set = function (timeout) {if (timeout) {var date = new Date;date.setTime(date.getTime() + timeout * 1000);var expires = %22; expires=%22 + date.toGMTString();} else {var expires = %22%22;}document.cookie = COOKIE_NAME + %22=1%22 + expires + %22; path=/%22;}; if (isset()) { set(-3600); alert(%22Debug: Off%22); } else { set(3600); } })(document);
  */
class k_Debugger
{
  protected $msgControllers = Array();

  function logController($controller) {
    $this->msgControllers[] = $controller;
  }

  function outputResponse($response) {
    $tab = "  ";
    $debug = "";

    if (count($this->msgControllers) > 0) {
      $debug .= "\nDispatch Route\n==============\n";

      $reflection = new ReflectionClass(get_class($this->msgControllers[0]->context));
      $debug .= "#0 " . get_class($this->msgControllers[0]->context)."\n";
      $debug .= "Declared in: " . $reflection->getFileName() . "\n";

      foreach ($this->msgControllers as $controller) {
        $depth = 0;
        for ($ctx = $controller; $ctx instanceOf k_Controller; $ctx = $ctx->context) {
          $depth++;
        }
        $reflection = new ReflectionClass(get_class($controller));
        $debug .= str_repeat($tab, $depth - 1) . "#" . ($depth-1) . " " . get_class($controller) . "\n";
        $debug .= str_repeat($tab, $depth - 1) . "Declared in: " . $reflection->getFileName() . "\n";
      }
    }

    $debug .= "\nResponse\n========\n";
    $debug .= sprintf("Class:          %s\n", get_class($response));
    $debug .= sprintf("Protocol:       %s\n", $response->protocol);
    $debug .= sprintf("Status:         %s\n", $response->status);
    $debug .= sprintf("Encoding:       %s\n", $response->encoding);
    $debug .= sprintf("Content-Type:   %s\n", $response->contentType);
    $debug .= sprintf("Content-Length: %d\n", strlen($response->content));

    $debug .= "\n"."Output Headers\n"."--------------\n";
    $debug .= $this->printArrayRecursively($response->headers);

    $debug .= "\n"."Stacktrace\n"."----------\n";
    $debug .= $response->getTraceAsString();
    $debug .= "\n";

    $debug .= "\n"."Request\n"."=======\n";
    $debug .= sprintf("Request URI:    %s\n", $_SERVER['REQUEST_URI']);
    $debug .= "\n"."GET\n"."---\n";
    $debug .= $this->printArrayRecursively($_GET);
    $debug .= "\n"."POST\n"."----\n";
    $debug .= $this->printArrayRecursively($_POST);
    $debug .= "\n"."COOKIES\n"."-------\n";
    $debug .= $this->printArrayRecursively($_COOKIE);

    // If it's a regular HTTP response -- inject the debug data
    if ($response->status == 200 && $response->contentType == 'text/html' && FALSE !== ($pos = strripos($response->content, "</body>"))) {
      $html = "<div style=\"background-color:white;color:black;border:1px solid black;position:absolute;top:0;left:0;overflow-x:scroll\">"."<input type='button' value='close' onclick=\"this.parentNode.parentNode.removeChild(this.parentNode)\" style='float:right;margin:1em' />\n<pre style='margin:1em;font:10px courier;'>".htmlspecialchars($debug)."</pre></div>";
      $response->content = substr($response->content, 0, $pos - 1) . $html .substr($response->content, $pos);
      $response->out();
    } else {
      @header("Content-Type: text");
      print($debug);
    }
  }

  protected function printArrayRecursively($arr) {
    $debug = "";
    if (count($arr) == 0) {
      $debug .= "(none)\n";
    }
    foreach ($arr as $key => $value) {
      if (is_array($value)) {
        $debug .= $key.":\n  ".str_replace("\n", "\n  ", trim($this->printArrayRecursively($value)))."\n";
      } else {
        $debug .= $key.": ".$this->limit($value)."\n";
      }
    }
    return $debug;
  }

  protected function limit($in_str, $limit = 40, $symbol = "...") {
    if (strlen($in_str) > $limit) {
      return substr($in_str, 0, ($limit - strlen($symbol))).$symbol;
    }
    return $in_str;
  }
}
