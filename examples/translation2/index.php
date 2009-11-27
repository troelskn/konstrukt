<?php
require_once 'konstrukt/konstrukt.inc.php';

class DanishLanguage implements k_Language {
  function name() {
    return 'Danish';
  }
  function isoCode() {
    return 'da';
  }
}

class HindiLanguage implements k_Language {
  function name() {
    return 'Hindi';
  }
  function isoCode() {
    return 'hi';
  }
}

class GermanLanguage implements k_Language {
  function name() {
    return 'Deutsch';
  }
  function isoCode() {
    return 'de';
  }
}

class FrenchLanguage implements k_Language {
  function name() {
    return 'French';
  }
  function isoCode() {
    return 'de';
  }
}

class MyLanguageLoader implements k_LanguageLoader {
  // @todo The language will often not be set on runtime, e.g. an
  //       intranet where the user can chose him or her own language?
  //       How could one accommodate for this?
  function load(k_Context $context) {
    require_once 'PEAR.php';
    require_once 'HTTP.php';

    if($context->query('lang') == 'da') {
      return new DanishLanguage();
    } else if($context->query('lang') == 'fr') {
      return new FrenchLanguage();
    } else if($context->query('lang') == 'de') {
      return new GermanLanguage();
    } else if($context->query('lang') == 'hi') {
      return new HindiLanguage();
    } else if($context->query('lang')) {
      return new DanishLanguage();
    }

    $supported = array("da" => true, "en-US" => true);
    $language = HTTP::negotiateLanguage($supported);
    if (PEAR::isError($language)) {
      // fallback language in case of unable to negotiate
      return new k_DanishLanguage();
    }

    if ($language == 'en-US') {
        return new k_EnglishLanguage();
    }
    return new k_DanishLanguage();
  }
}

class k_Translation2Translator implements k_Translator {
  protected $translation2;
  function __construct($lang) {
    $params = array(
      'filename' => dirname(__FILE__) . '/strings.xml',
    );
    require_once 'Translation2.php';
    $this->translation2 = Translation2::factory('XML', $params);
    if (PEAR::isError($this->translation2)) {
        throw new Exception('Could not start Translation2: ' . $this->translation2->getMessage());
    }
    $res = $this->translation2->setLang($lang);
    if (PEAR::isError($res)) {
        throw new Exception('Could not setLang()');
    }
  }

  function translate($phrase, k_Language $language = null) {
    // Translation2 groups translations with pageID(). This can
    // be accommodated like this
    if (is_array($phrase) && count($phrase) == 2) {
        return $this->translation2->get($phrase[0], $phrase[1]);
    }
    return $this->translation2->get($phrase, 'basic');
  }
}

class SimpleTranslatorLoader implements k_TranslatorLoader {
  function load(k_Context $context) {
    return new k_Translation2Translator($context->language()->isoCode());
  }
}

class Root extends k_Component {
  function map($name) {
    if($name == 'template') {
      return 'Template';
    }
  }
  function renderHtml() {
    return '<p>'.$this->translator()->translate('How are you?').'</p>';
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
