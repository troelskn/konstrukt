<?php
require_once dirname(__FILE__) . '/../config/global.inc.php';
k()
  // Use container for wiring of components
  ->setComponentCreator(new k_InjectorAdapter(create_container()))
  // Location of debug logging
  ->setLog($debug_log_path)
  // Enable/disable in-browser debugging
  ->setDebug($debug_enabled)
  // Dispatch request
  ->run('components_Root')
  ->out();
