<?php
require_once 'konstrukt/konstrukt.inc.php';

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
      return new k_EnglishLanguage();
    }
    return new k_EnglishLanguage();
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
    return new k_DefaultTranslator($phrases);
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
