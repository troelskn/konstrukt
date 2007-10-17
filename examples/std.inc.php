<?php
// This is the default bootstrap for Konstrukt
// Most of this setup is environment-dependent and really outside the scope of Konstrukt.
// You can copy the contents of this file into your bootstrap (index.php), or you can
// just include this file.

// Adds /lib/ to the path
ini_set('include_path',
  "lib"
  .PATH_SEPARATOR.dirname(dirname(__FILE__))."/lib"
  .PATH_SEPARATOR.ini_get('include_path')
);

// Loads Konstrukt global symbols
require_once 'k.php';

// This is a default error-handler, which simply converts errors to exceptions
// Konstrukt doesn't need this setup, but it's a pretty sane choice.
// If this makes no sense to you, just let it be. It basically means that old-style errors are
// converted into exceptions instead. This allows a simpler error-handling.
error_reporting(E_ALL | E_STRICT);
function exceptions_error_handler($severity, $message, $filename, $lineno) {
  if (error_reporting() == 0) {
    return;
  }
  if (error_reporting() & $severity) {
    throw new ErrorException($message, 0, $severity, $filename, $lineno);
  }
}
set_error_handler('exceptions_error_handler');

// This is a default exceptions-handler. For debugging, it's practical to get a readable
// trace dumped out at the top level, rather than just a blank screen.
// If you use something like Xdebug, you may want to skip this part, since it already gives
// a similar output.
// For production, you should replace this handler with something, which logs the error,
// and doesn't dump a trace. Failing to do so could be a security risk.
function debug_exception_handler($ex) {
  if (php_sapi_name() == 'cli') {
    echo "Error (code:".$ex->getCode().") :".$ex->getMessage()."\n at line ".$ex->getLine()." in file ".$ex->getFile()."\n";
    echo $ex->getTraceAsString()."\n";
  } else {
    echo "<p style='font-family:helvetica,sans-serif'>\n";
    echo "<b>Error :</b>".$ex->getMessage()."<br />\n";
    echo "<b>Code :</b>".$ex->getCode()."<br />\n";
    echo "<b>File :</b>".$ex->getFile()."<br />\n";
    echo "<b>Line :</b>".$ex->getLine()."</p>\n";
    echo "<div style='font-family:garamond'>".nl2br(htmlspecialchars($ex->getTraceAsString()))."</div>\n";
  }
  exit -1;
}
set_exception_handler('debug_exception_handler');
