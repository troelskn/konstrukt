<?php
/**
  * Wrapper to http sessions.
  */
class k_http_Session
{
  function __construct() {
    if (session_id() == "") {
      session_start();
    }
  }

  function getSessionId() {
    return session_id();
  }

  function destroy() {
    $_SESSION = Array();
    if (isset($_COOKIE[session_name()])) {
      setcookie(session_name(), '', time()-42000, '/');
    }
    session_destroy();
    $filename = realpath(session_save_path()).DIRECTORY_SEPARATOR.session_id();
    if (is_file($filename) && is_writable($filename)) {
      unlink($filename);
    }
  }

  function regenerateId() {
    session_regenerate_id();
  }

  function close() {
    session_write_close();
  }

  /**
    * Returns a reference to a session-space (array).
    *
    * If the session-space doesn't exist, it will be created silently.
    * @param    string    $name    Name of session-space or
    *                              NULL for $_SESSION
    */
  public function & get($name = NULL) {
    if (is_null($name)) {
      return $_SESSION;
    }
    $name = strtolower($name);
    if (!isset($_SESSION[$name])) {
      $_SESSION[$name] = Array();
    }
    $space =& $_SESSION[$name];
    return $space;
  }
}
