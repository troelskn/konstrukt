<?php

require_once 'includes/markdown.php';
require_once 'includes/geshi/geshi.php';

class MyMarkdownTransformer extends MarkdownExtra_Parser {
  protected $auto_id = 3;
  protected $transformers = array();
  /**
   * Register a plugin transform-handler.
   *
   *   ..foo
   *     content, that is sent to the handler for foo
   *
   * @param $names       A string or array of strings of keywords for this plugin.
   * @param $transformer A callback or an object, implementing a `transform` method.
   */
  function addPlugin($names = array(), $transformer) {
    if (is_string($names)) {
      $names = array($names);
    }
    if (!(is_object($transformer) || is_callable($transformer))) {
      throw new Exception("Illegal argument. Transformer must be an object or a valid callback.");
    }
    foreach ($names as $name) {
      $this->transformers[strtoupper($name)] = $transformer;
    }
  }
  /**
   * Transforms input text to html.
   */
  function transform($text) {
    $text = preg_replace("/\015\012|\015|\012/", "\n", $text);
    $processed = "";
    foreach (preg_split('~(\n[ \n]*\n)~S', $text, -1, PREG_SPLIT_DELIM_CAPTURE) as $part) {
      if (preg_match('~^(\.\.\s*([^:\n]+)[^\n]*)\n?(.*)$~sS', $part, $section)) {
        $name = strtoupper(trim($section[2]));
        if (isset($this->transformers[$name])) {
          if (is_callable($this->transformers[$name])) {
            $processed .= call_user_func($this->transformers[$name], $section[3], $name, $section[1]);
          } else {
            $processed .= $this->transformers[$section[1]]->transform($section[3], $name, $section[1]);
          }
        } else {
          $processed .= $part;
        }
      } else {
        $processed .= $part;
      }
    }
    return parent::transform($processed);
  }
  function _doHeaders_makeBlock($level, $matches, $offset) {
    if (!empty($matches[$offset + 1])) {
      $attr = ' id="' . $matches[$offset + 1] . '"';
    } elseif (is_integer($this->auto_id) && $level <= $this->auto_id) {
      $attr = ' id="' . trim(preg_replace('~[^a-z0-9]+~', '-', strtolower($matches[$offset])), '-') . '"';
    } else {
      $attr = "";
    }
		return "<h$level$attr>".$this->runSpanGamut($matches[$offset])."</h$level>";
  }
	function _doHeaders_callback_setext($matches) {
		if ($matches[3] == '-' && preg_match('{^- }', $matches[1]))
			return $matches[0];
		$level = $matches[3]{0} == '=' ? 1 : 2;
    $block = $this->_doHeaders_makeBlock($level, $matches, 1);
		return "\n" . $this->hashBlock($block) . "\n\n";
	}
	function _doHeaders_callback_atx($matches) {
		$level = strlen($matches[1]);
    $block = $this->_doHeaders_makeBlock($level, $matches, 2);
		return "\n" . $this->hashBlock($block) . "\n\n";
	}
}

class DocumentationTransformer {
  function __construct() {
    $this->transformer = new MyMarkdownTransformer();
    $this->transformer->addPlugin('php', array($this, 'transformPHP'));
    $this->transformer->addPlugin('sql', array($this, 'transformSql'));
    $this->transformer->addPlugin('html', array($this, 'transformHtml'));
    $this->transformer->addPlugin('raw', array($this, 'transformRaw'));
    $this->transformer->addPlugin('note', array($this, 'wrapNote'));
  }

  function transform($input) {
    return $this->transformer->transform($input);
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

  protected function inlineTransformPHP($args) {
    $geshi = $this->getGeshi(str_replace(array('&lt;', '&gt;', '&amp;', '&quot;'), array('<', '>', '&', '"'), $args[1]));
    $geshi->header_type = GESHI_HEADER_NONE;
    return $geshi->parse_code();
  }

  function transformPHP($data, $keyword, $header) {
    $geshi = $this->getGeshi($data);
    return $geshi->parse_code();
  }

  function transformHTML($data, $keyword, $header) {
    return "<pre>".preg_replace_callback('~(&lt;\?php.+?\?&gt;)~mi', array($this, 'inlineTransformPHP'), htmlspecialchars($data))."</pre>\n";
  }

  function transformSQL($data, $keyword, $header) {
    $geshi = new GeSHi($data, 'sql');
    $geshi->enable_keyword_links(false);
    return $geshi->parse_code();
  }

  function transformRaw($data, $keyword, $header) {
    return "<div class=\"nostyle\">" . $data . "</div>\n";
  }

  function wrapNote($data, $keyword, $header) {
    return "<p class=\"note\" markdown=\"1\">Note: " . $data . "</p>\n";
  }
}
