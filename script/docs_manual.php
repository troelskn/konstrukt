#!/usr/bin/env php
<?php
set_include_path(
  get_include_path() . PATH_SEPARATOR . dirname(__FILE__));
require_once 'includes/documentation.php';

class ManualProcessor {
  protected $transformer;

  protected $indir;
  protected $outdir;

  protected $head;
  protected $foot;

  function __construct($indir, $outdir) {
    $this->transformer = new DocumentationTransformer();
    $this->indir = rtrim($indir, "/\\")."/";
    $this->outdir = rtrim($outdir, "/\\")."/";
    $this->head = file_get_contents(dirname(__FILE__).'/templates/head.tpl.html');
    $this->foot = file_get_contents(dirname(__FILE__).'/templates/foot.tpl.html');
  }

  public function run() {
    echo "Creating manual\n\n";
    $dir = new RecursiveDirectoryIterator($this->indir, RecursiveDirectoryIterator::KEY_AS_FILENAME);
    foreach ($dir->getChildren() as $file) {
      if ($file->isFile() && preg_match('/\.txt$/', $file->getFileName())) {
        echo "Processing: $file\n";
        $this->processFile($file);
      }
    }
  }

  protected function processFile($file) {
    $html = $this->transformer->transform(file_get_contents($file->getPathName()));
    $basename = basename($file->getFileName(), '.txt');

    $title = ucwords(str_replace('-', ' ', $basename));

    $content = str_replace('{TITLE}', htmlspecialchars($title, ENT_QUOTES), $this->head) . $html . $this->foot;
    $outfile = $this->outdir . $basename . '.html';
    file_put_contents($outfile, $content);
  }
}

$transformer = new ManualProcessor(getcwd()."/docs/txt", getcwd()."/docs");
$transformer->run();
