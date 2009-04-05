<?php
require_once '../../lib/konstrukt/konstrukt.inc.php';
require_once 'smarty/libs/Smarty.class.php';
date_default_timezone_set('Europe/Paris');

class HelloComponent extends k_Component {
  function renderHtml() {
    $smarty = new Smarty();
    $smarty->compile_check = true;
    $smarty->debugging = true;
    $smarty->assign("Name", "Fred Irving Johnathan Bradley Peppergill");
    $smarty->assign("FirstName", array("John", "Mary", "James", "Henry"));
    $smarty->assign("LastName", array("Doe", "Smith", "Johnson", "Case"));
    $smarty->assign(
      "Class", array(
        array("A", "B", "C", "D"), array("E", "F", "G", "H"),
        array("I", "J", "K", "L"), array("M", "N", "O", "P")));
    $smarty->assign(
      "contacts", array(
        array("phone" => "1", "fax" => "2", "cell" => "3"),
        array("phone" => "555-4444", "fax" => "555-3333", "cell" => "760-1234")));
    $smarty->assign("option_values", array("NY", "NE", "KS", "IA", "OK", "TX"));
    $smarty->assign("option_output", array("New York", "Nebraska", "Kansas", "Iowa", "Oklahoma", "Texas"));
    $smarty->assign("option_selected", "NE");
    return $smarty->fetch("index.tpl");
  }
}

if (realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__) {
  k()->run('HelloComponent')->out();
}
