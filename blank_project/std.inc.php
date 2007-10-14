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

// These classes can't be autoloaded, and so must be manually included
// Everything else can be autoloaded, by adding the autoload callback (See end of this file)
require_once 'k/classloader.php';
require_once 'k/staticadapter.php';

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
  echo "<p style='font-family:helvetica,sans-serif'>";
  echo "<b>Error :</b>".$ex->getMessage()."<br />";
  echo "<b>Code :</b>".$ex->getCode()."<br />";
  echo "<b>File :</b>".$ex->getFile()."<br />";
  echo "<b>Line :</b>".$ex->getLine()."</p>";
  echo "<div style='font-family:garamond'>".nl2br($ex->getTraceAsString())."</div>";
  exit;
}
set_exception_handler('debug_exception_handler');

// Here we hook up the default autoloader. With this, you don't need to explicitly include
// files, as long as they follow the Konstrukt naming scheme.
// Note that Konstrukt differs slightly from PEAR, in that all filenames are lowercase.
// This is to ensure portability across filesystems, with casesensitive filenames (*nix)
spl_autoload_register(Array('k_ClassLoader', 'autoload'));
