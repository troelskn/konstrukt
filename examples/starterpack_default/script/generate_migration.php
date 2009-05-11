#!/usr/bin/env php
<?php
require_once(dirname(__FILE__) . '/../config/global.inc.php');
$dir_migrations = (dirname(__FILE__) . '/migrations');
$base_name = date('YmdHis') . '.php';
$destination_file_name = $dir_migrations . DIRECTORY_SEPARATOR . $base_name;

$php = '#!/usr/bin/env php
<?php
require_once(dirname(__FILE__) . \'/../../config/global.inc.php\');
$container = create_container();
//$db = $container->create(\'PDO\');
';

echo "Writing file '$base_name'.\n";
file_put_contents($destination_file_name, $php);
chmod($destination_file_name, 0755);