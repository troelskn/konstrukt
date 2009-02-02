<?php
require_once '../../lib/konstrukt/konstrukt.inc.php';

class RegistrationForm extends k_Component {
  protected $failures = array();
  function map($name) {
    if ($name == 'thanks') {
      return 'Thanks';
    }
  }
  function POST() {
    if ($this->validate()) {
      // do stuff with data here
      throw new k_SeeOther($this->url('thanks', array('flare' => 'You have been registered .. or something')));
    }
    return $this->GET();
  }
  function validate() {
    $this->failures = array();
    if (!trim($this->body('first_name'))) {
      $this->failures[] = "You must enter your first name";
    }
    if (!trim($this->body('last_name'))) {
      $this->failures[] = "You must enter your last name";
    }
    if (!trim($this->body('question'))) {
      $this->failures[] = "You must enter your question";
    }
    return count($this->failures) === 0;
  }
  function renderHtml() {
    $error = array();
    foreach ($this->failures as $failure) {
      $error[] = sprintf('<p class="error">%s</p>', htmlspecialchars($failure));
    }
    $error = implode("\n", $error);

    return sprintf(
      '
<h1>Registration Form</h1>
<form method="post" action="%s">
%s
<p>
  <label>
  First Name:
  <br/>
  <input type="text" name="first_name" value="%s" />
  </label>
</p>
<p>
  <label>
  Last Name:
  <br/>
  <input type="text" name="last_name" value="%s" />
  </label>
</p>
<p>
  <label>
  Question:
  <br/>
  <textarea name="question">%s</textarea>
  </label>
</p>
<p>
  <input type="submit" value="Register" />
</p>
</form>
',
      htmlspecialchars($this->url()),
      $error,
      htmlspecialchars($this->body('first_name')),
      htmlspecialchars($this->body('last_name')),
      htmlspecialchars($this->body('question')));
  }
}

class Thanks extends k_Component {
  function GET() {
    return sprintf("<p>%s</p>", htmlspecialchars($this->query('flare')));
  }
}

if (realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__) {
  k()->run('RegistrationForm')->out();
}
