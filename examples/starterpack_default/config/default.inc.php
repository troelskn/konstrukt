<?php
// This loads the default environment (development).
//
// You shouldn't alter this file.
// Instead, create a file called `SITENAME.inc.php`, where SITENAME is the name of the site.
// Check all `SITENAME.inc.php` into the repository.
// On each site (server), create a link:
//
//     ln -s SITENAME.inc.php local.inc.php
//
// If your server doesn't support symlinks (Windows), you can instead use:
//
//   copy default.inc.php local.inc.php
//
//, and change the include inside `local.inc.php`.
//
// Don't check `local.inc.php` into the repository.
//
require_once dirname(__FILE__) . '/development.inc.php';