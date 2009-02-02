<?php
require_once '../../lib/konstrukt/konstrukt.inc.php';

// You must have ZendFramework on your path
require_once 'Zend/Form.php';
require_once 'Zend/View.php';

class ZfRegistrationForm extends k_Component {
  protected $form;
  function map($name) {
    if ($name == 'thanks') {
      return 'ZfThanks';
    }
  }
  function POST() {
    if ($this->form()->isValid($this->body())) {
      // do stuff with data here
      throw new k_SeeOther($this->url('thanks', array('flare' => 'You have been registered .. or something')));
    }
    return $this->GET();
  }
  function renderHtml() {
    return $this->form()->render();
  }
  function form() {
    if (!isset($this->form)) {
      $form = new Zend_Form();
      $form->setAction($this->url());
      $form->setMethod('post');

      // Create and configure username element:
      $username = $form->createElement('text', 'username');
      $username->setLabel("Username");
      $username->addValidator('alnum');
      $username->addValidator('regex', false, array('/^[a-z]+/'));
      $username->addValidator('stringLength', false, array(6, 20));
      $username->setRequired(true);
      $username->addFilter('StringToLower');

      // Create and configure password element:
      $password = $form->createElement('password', 'password');
      $password->setLabel("Password");
      $password->addValidator('StringLength', false, array(6));
      $password->setRequired(true);

      // Add elements to form:
      $form->addElement($username);
      $form->addElement($password);
      // use addElement() as a factory to create 'Login' button:
      $form->addElement('submit', 'login', array('label' => 'Login'));

      // Since we're using this outside ZF, we need to supply a default view:
      $form->setView(new Zend_View());

      $this->form = $form;
    }
    return $this->form;
  }
}

class ZfThanks extends k_Component {
  function GET() {
    return sprintf("<p>%s</p>", htmlspecialchars($this->query('flare')));
  }
}

if (realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__) {
  k()->run('ZfRegistrationForm')->out();
}
