<?php
require_once dirname(__FILE__) . '/../config/global.inc.php';
k()
  // Enable file logging
  ->setLog(dirname(__FILE__) . '/../log/debug.log')
  // Uncomment the nexct line to enable in-browser debugging
  //->setDebug()
  // Dispatch request
  ->run('components_Root')
  ->out();
