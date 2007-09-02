<?php
require_once '../../examples/std.inc.php';
require_once 'models.inc.php';

class Root extends k_Dispatcher
{
  function GET() {
    $db = new PDO("sqlite::memory:", "root");
    $heroes = new SuperHeroes($db);
    $villains = new SuperVillains($db);

    $list_of_heroes = new k_DataList($this, 'heroes');
    $list_of_heroes->pageSize = 5;
    $list_of_heroes->template = "datalist.tpl.php";
    $list_of_heroes->setFieldNames(Array('name', 'rating'));
    $list_of_heroes->setQueryCallback(Array($heroes, 'query'));
    $list_of_heroes->setCountCallback(Array($heroes, 'count'));

    $list_of_villains = new k_DataList($this, 'villains');
    $list_of_villains->pageSize = 5;
    $list_of_villains->template = "datalist.tpl.php";
    $list_of_villains->setFieldNames(Array('name', 'rating'));
    $list_of_villains->setQueryCallback(Array($villains, 'query'));
    $list_of_villains->setCountCallback(Array($villains, 'count'));

    return $list_of_heroes->execute() . "<br/>" . $list_of_villains->execute();
  }
}

//////////////////////////////////////////////////////////////////////////////

$application = new Root();
$application->dispatch();
