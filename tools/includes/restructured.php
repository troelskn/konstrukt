<?php
require_once 'geshi/geshi.php';

class restructured_Parser
{
  protected $buffer = Array();
  protected $section = 'TEXT';

  public $children = Array();

  function parse($text) {
    $text = preg_replace("/\015\012|\015|\012/", "\n", $text);
    foreach (explode("\n", $text) as $line) {
      $this->accept($line);
    }
    return $this->getToken();
  }

  function accept($line) {
    if (preg_match('~^[=]+$~', $line, $m)) {
      if (count($this->buffer) > 0) {
        $lastline = array_pop($this->buffer);
        $this->flush();
        $this->children[] = Array('HEADER1', $lastline);
      }
    } else if (preg_match('~^[-]+$~', $line, $m)) {
      if (count($this->buffer) > 0) {
        $lastline = array_pop($this->buffer);
        $this->flush();
        $this->children[] = Array('HEADER2', $lastline);
      }
    } else if (preg_match('~^(#+)(.+)$~', $line, $m)) {
      $this->flush();
      $this->children[] = Array('HEADER'.strlen($m[1]), $m[2]);
    } else if (preg_match('~^\.\.\s+(.+)$~', $line, $m)) {
      $this->flush();
      $this->section = 'DIRECTIVE';
      $this->buffer[] = $line;
    } else if (preg_match('~^::\s*(.+)\s*$~', $line, $m)) {
      $this->flush();
      $this->section = strtoupper($m[1]);
    } else if (preg_match('~^\*\s+.+$~', $line)) {
      if ($this->section != 'LIST') {
        $this->flush();
      }
      $this->section = 'LIST';
      $this->buffer[] = $line;
    } else if ($line == "::") {
      $this->flush();
      $this->section = 'PHP';
    } else if ($line == "") {
      $this->flush();
    } else {
      $this->buffer[] = $line;
    }
  }

  protected function createSection($type) {
    if ($type == 'DIRECTIVE') {
      return new restructured_Parser_Directive();
    }
    if ($type == 'LIST') {
      return new restructured_Parser_ListSection();
    }
    return new restructured_Parser_TextSection($type);
  }

  protected function flush() {
    while (count($this->buffer) > 0) {
      $continue = TRUE;
      $parser = $this->createSection($this->section);
      while ($continue && count($this->buffer) > 0) {
        $continue = $parser->accept(array_shift($this->buffer));
      }
      $this->children[] = $parser->getToken();
      $this->section = 'TEXT';
    }
  }

  function getToken() {
    $this->flush();
    return Array('ROOT', $this->children);
  }
}

class restructured_Parser_TextSection
{
  protected $buffer = Array();

  function __construct($type = 'TEXT') {
    $this->type = $type;
  }

  function accept($line) {
    if ($line == "") {
      return FALSE;
    } else {
      $this->buffer[] = $line == "\\" ? "" : $line;
    }
    return TRUE;
  }

  function getToken() {
    return Array($this->type, implode("\n", $this->buffer));
  }
}

class restructured_Parser_ListSection
{
  protected $buffer = Array();

  function accept($line) {
    if (preg_match('~^\*\s*(.+)$~', $line, $m)) {
      $this->buffer[] = $m[1];
      return TRUE;
    } else if (preg_match('~^\\\s*(.+)$~', $line, $m) && count($this->buffer) > 0) {
      $this->buffer[count($this->buffer)] .= "\n" . $m[1];
    }
    return FALSE;
  }

  function getToken() {
    return Array('LIST', $this->buffer);
  }
}

class restructured_Parser_Directive
{
  protected $buffer = Array();

  function accept($line) {
    if ($line == "") {
      return FALSE;
    } else {
      $this->buffer[] = $line;
    }
    return TRUE;
  }

  function getToken() {
    $line = array_shift($this->buffer);
    if (!preg_match('~^\.\.\s+(\w+)::\s*(.+)$~', $line, $m)) {
      throw new Exception("Malformed directive");
    }
    $type = $m[1];
    $args = $m[2];
    $properties = Array();
    foreach ($this->buffer as $line) {
      if (!preg_match('~^\s*:(\w+):\s*(.+)$~', $line, $m)) {
        throw new Exception("Malformed directive");
      }
      $properties[$m[1]] = $m[2];
    }
    return Array(strtoupper($type), Array($args, $properties));
  }
}

class restructured_Transformer
{
  public $title = "";
  public $newlines = TRUE;

  function transform($token) {
    $name = 'transform'.$token[0];
    $args = $token[1];
    if (!method_exists($this, $name)) {
      throw new Exception("No transform for token '".$token[0]."'");
    }
    return $this->$name($args);
  }

  protected function transformRoot($args) {
    return $this->transformSection($args);
  }

  protected function transformSection($args) {
    $out = "";
    foreach ($args as $token) {
      $out .= $this->transform($token);
    }
    return $out;
  }

  protected function transformHeader1($args) {
    if (!$this->title) {
      $this->title = $args;
    }
    return "<h1>".htmlspecialchars($args, ENT_QUOTES)."</h1>\n";
  }

  protected function transformHeader2($args) {
    return "<h2>".htmlspecialchars($args, ENT_QUOTES)."</h2>\n";
  }

  protected function transformHeader3($args) {
    return "<h3>".htmlspecialchars($args, ENT_QUOTES)."</h3>\n";
  }

  protected function transformText($args) {
    return "<p>".$this->newlines($this->formatInline($args))."</p>\n";
  }

  protected function getGeshi($text) {
    $geshi = new GeSHi($text, 'php');
    $geshi->enable_classes();
    $geshi->enable_keyword_links(false);
    $geshi->language_data['KEYWORDS'][1][] = "throw";
    $geshi->language_data['KEYWORDS'][1][] = "catch";
    $geshi->language_data['KEYWORDS'][2][] = "parent";
    $geshi->language_data['KEYWORDS'][2][] = "public";
    $geshi->language_data['KEYWORDS'][2][] = "protected";
    $geshi->language_data['KEYWORDS'][2][] = "private";
    $geshi->language_data['KEYWORDS'][2][] = "abstract";
    $geshi->language_data['KEYWORDS'][2][] = "const";
    return $geshi;
  }

  protected function newlines($text) {
    if ($this->newlines) {
      return nl2br($text);
    }
    return $text;
  }

  protected function transformPHP($args) {
    $geshi = $this->getGeshi($args);
    return $geshi->parse_code();
  }

  protected function inlineTransformPHP($args) {
    $geshi = $this->getGeshi(str_replace(Array('&lt;', '&gt;', '&amp;', '&quot;'), Array('<', '>', '&', '"'), $args[1]));
    $geshi->header_type = GESHI_HEADER_NONE;
    return $geshi->parse_code();
  }

  protected function transformHTML($args) {
    return "<pre>".preg_replace_callback('~(&lt;\?php.+?\?&gt;)~mi', Array($this, 'inlineTransformPHP'), htmlspecialchars($args))."</pre>\n";
  }

  protected function transformRaw($args) {
    return "<div class=\"nostyle\">".$args."</div>\n";
  }

  protected function transformNote($args) {
    return "<div class=\"note\">Note: ".$this->newlines($this->formatInline($args))."</div>\n";
  }

  protected function transformList($args) {
    $out = "<ul>\n";
    foreach ($args as $line) {
      $out .= "  <li>".$this->formatInline($line)."</li>\n";
    }
    $out .= "</ul>\n";
    return $out;
  }

  protected function transformImage($args) {
    $attributes = "";
    foreach (array_merge(Array('src' => $args[0], 'alt' => $args[0]), $args[1]) as $key => $value) {
      $attributes .= " ". $key.'="'.htmlspecialchars($value, ENT_QUOTES).'"';
    }
    return "<p class=\"illustration\"><img".$attributes." /></p>";
  }

  protected function formatInline($text) {
    $text = htmlspecialchars($text, ENT_QUOTES);
    $text = preg_replace("/(``)(.*?)(``)/", "<code>\$2</code>", $text);
    $text = preg_replace("/(\*\*)(.*?)(\*\*)/", "<strong>\$2</strong>", $text);
    $text = preg_replace("/(\*)(.*?)(\*)/", "<em>\$2</em>", $text);
    $text = preg_replace("/\[\[(.*?)\|(.*?)\]\]/", "<a href=\"\$2\">\$1</a>", $text);
    return $text;
  }
}