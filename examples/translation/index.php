<?php
require_once 'konstrukt/konstrukt.inc.php';

class EnglishLanguage implements k_Language {
  function name() {
    return 'English';
  }
  function isoCode() {
    return 'en';
  }
}

class SwedishLanguage implements k_Language {
  function name() {
    return 'Swedish';
  }
  function isoCode() {
    return 'sv';
  }
}

class MyLanguageLoader implements k_LanguageLoader {
  function load(k_Context $context) {
    if($context->query('lang') == 'sv') {
      return new SwedishLanguage();
    } else if($context->query('lang') == 'en') {
      return new EnglishLanguage();
    }
    return new EnglishLanguage();
  }
}

class SimpleTranslator implements k_Translator {
  protected $phrases;
  function __construct($phrases = array()) {
    $this->phrases = $phrases;
  }
  function translate($phrase, k_Language $language = null) {
    return isset($this->phrases[$phrase]) ? $this->phrases[$phrase] : $phrase;
  }
}

class SimpleTranslatorLoader implements k_TranslatorLoader {
  function load(k_Context $context) {
    // Default to English
    $phrases = array(
      'Hello' => 'Hello',
      'Meatballs' => 'Meatballs',
    );
    if($context->language()->isoCode() == 'sv') {
      $phrases = array(
        'Hello' => 'Bork, bork, bork!',
        'Meatballs' => 'Swedish meatballs',
      );
    }
    return new SimpleTranslator($phrases);
  }
}

class Root extends k_Component {
  function map($name) {
    if($name == 'template') {
      return 'Template';
    }
  }
  function renderHtml() {
    return sprintf("<p>%s<br>Todays dinner: %s</p>", $this->translator()->translate('Hello'), $this->translator()->translate('Meatballs'));
  }
}

class Template extends k_Component {
  function renderHtml() {
    $template = new k_Template(dirname(__FILE__) .'/template.php');
    return $template->render($this);
  }
}

if (realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__) {
  k()->setLanguageLoader(new MyLanguageLoader())->setTranslatorLoader(new SimpleTranslatorLoader())->run('Root')->out();
}
