#!/usr/bin/env php
<?php
/**
 * package.xml generation script
 *
 * Create a package:
 *
 * <code>
 * $ php makepackage.php make
 * $ pear package package.xml
 * </code>
 *
 * Install a package from an url:
 *
 * <code>
 * $ pear install ./konstrukt-[version].tgz
 * </code>
 *
 * Upgrade a package from an url:
 *
 * <code>
 * $ pear upgrade ./konstrukt-[version].tgz
 * </code>
 *
 * @package konstrukt
 * @author  Lars Olesen <lars@legestue.net>
 */

require_once 'PEAR/PackageFileManager2.php';

$version   = '2.1.0';
$stability = 'stable';
$notes     = 'Added mime-based type-system. See UPGRADING for info on changes.';

PEAR::setErrorHandling(PEAR_ERROR_DIE);
$pfm = new PEAR_PackageFileManager2();
$pfm->setOptions(
  array(
    'baseinstalldir'    => '/',
    'filelistgenerator' => 'file',
    'packagedirectory'  => dirname(__FILE__) . '/../',
    'packagefile'       => 'package.xml',
    'addhiddenfiles'    => true,
    'ignore'            => array(
      '*script*',
      '*.svn*',
      '*.tgz',
    ),
    'dir_roles' => array(
      'test' => 'test',
      'examples' => 'doc',
      'docs' => 'doc',
      'lib' => 'php'
    ),
    'exceptions'        => array(
    ),
    'simpleoutput'      => true,
  )
);

$pfm->setPackage('konstrukt');
$pfm->setSummary('A HTTP-friendly framework of controllers for PHP5.');
$pfm->setDescription('
Konstrukt is a minimalistic framework which provides a foundation on which to build rather than a boxed solution to all problems. It focuses on the controller layer, and tries to encourage the developer to deal directly with the HTTP protocol instead of abstracting it away. Konstrukt uses a hierarchical controller pattern, which provides a greater level of flexibility than the popular routed front controller frameworks.');
$pfm->setUri('http://www.konstrukt.dk/');
$pfm->setLicense('LGPL License', 'http://www.gnu.org/licenses/lgpl.html');
$pfm->addMaintainer('lead', 'troelskn', 'Troels Knak-Nielsen', 'troelskn@gmail.com');
$pfm->addMaintainer('helper', 'anders.ekdahl', 'Anders Ekdahl', '');
$pfm->addMaintainer('helper', 'lsolesen', 'Lars Olesen', 'lsolesen@users.sourceforge.net');

$pfm->setPackageType('php');

$pfm->setAPIVersion($version);
$pfm->setReleaseVersion($version);
$pfm->setAPIStability($stability);
$pfm->setReleaseStability($stability);
$pfm->setNotes($notes);
$pfm->addRelease();

$pfm->clearDeps();
$pfm->setPhpDep('5.2.0');
$pfm->setPearinstallerDep('1.5.0');

$lib_files = array(
  'konstrukt/adapter.inc.php',
  'konstrukt/charset.inc.php',
  'konstrukt/konstrukt.inc.php',
  'konstrukt/logging.inc.php',
  'konstrukt/response.inc.php',
  'konstrukt/virtualbrowser.inc.php',
);


foreach ($lib_files as $file) {
  $pfm->addInstallAs('lib/' . $file, $file);
}

$pfm->generateContents();

if (isset($_GET['make']) || (isset($_SERVER['argv']) && @$_SERVER['argv'][1] == 'make')) {
  echo "\nCreating PEAR package file\n";
  if ($pfm->writePackageFile()) {
    echo "\npackage.xml written\n\n";
    exit;
  }
} else {
  $pfm->debugPackageFile();
}
