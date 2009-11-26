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

class Root extends k_Component {
  function renderHtml() {
    return sprintf("<p>Current language is: %s (%s)</p>", $this->language()->name(), $this->language()->isoCode());
  }
}

if (realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__) {
  k()->setLanguageLoader(new MyLanguageLoader())->run('Root')->out();
}
