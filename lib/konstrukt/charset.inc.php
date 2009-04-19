<?php

  /*
notes on charsets
-----------------

two problems, regarding charsets:
  #1 what encoding is the internal string in?
    -> technically, the correct thing to do, is to make utf-8 default, but this conflicts with PHP defaulting to latin1
    -> alternatively, latin1 could be default, allowing the user to specify that data is utf-8
  #2 what encoding should the output be in (Eg. what is the charset-part of the Content-Type header)?
    -> if specified, then use that
    -> else use a default, based on content-type

- in-memory strings
- external i/o (http-streams):
  - input (form-submit, query-string, uri)
  - output:
    - encoding
    - header (Content-Type)
- internal i/o:
  - files (php-sources, templates)
  - database-connection
  - database-storage
  - and more ...

*/

  /**
   * A responsecharset governs how the response is sent back to the client.
   * the httpresponse holds the content as utf-8, so each responsecharset should be able to convert
   * from this base
   */
interface k_charset_ResponseCharset {
  function name();
  function encode($utf8_string);
}

class k_charset_Utf8 implements k_charset_ResponseCharset {
  /**
    * @return string
    */
  function name() {
    return 'utf-8';
  }
  /**
    * @param string
    * @return string
    */
  function encode($utf8_string) {
    return $utf8_string;
  }
}

class k_charset_Latin1 implements k_charset_ResponseCharset {
  function name() {
    return 'iso-8859-1';
  }
  function encode($utf8_string) {
    return utf8_decode($utf8_string);
  }
}

/**
 * A strategy for encoding/decoding charsets
 * The strategy is a factory for the Responsecharset as well as a filter to convert input to UTF-8
 */
interface k_charset_CharsetStrategy {
  function decodeInput($input);
  function responseCharset();
}

/**
 * UTF-8
 * This is the preferred configuration
 * If you're using PHP < 6
 * You should use the [mb-string extension](http://docs.php.net/manual/en/mbstring.overload.php) to replace the built-ins. This is not crucial, but a good idea none the less.
 * You also need to make sure that all files (php and templates) are utf-8 encoded
 * If you use a database, make sure that the transfer charset is utf-8 (on mysql use: SET NAMES = 'utf8')
 */
class k_charset_Utf8CharsetStrategy implements k_charset_CharsetStrategy {
  function decodeInput($input) {
    return $input;
  }
  /**
    * @return k_charset_Utf8
    */
  function responseCharset() {
    return new k_charset_Utf8();
  }
}

/**
 * ISO-8859-1
 * While this is the native solution for PHP < 6, it isn't recommended for new Konstrukt applications.
 */
class k_charset_Latin1CharsetStrategy implements k_charset_CharsetStrategy {
  /**
    * @param mixed
    * @return mixed
    */
  function decodeInput($input) {
    // http://talks.php.net/show/php-best-practices/26
    $in = array(&$input);
    while (list($k,$v) = each($in)) {
      foreach ($v as $key => $val) {
        if (!is_array($val)) {
          $in[$k][$key] = utf8_encode($val);
          continue;
        }
        $in[] =& $in[$k][$key];
      }
    }
    unset($in);
    return $input;
  }
  function responseCharset() {
    return new k_charset_Latin1();
  }
}
