<?php
require_once '../includes/restructured.php';

class ManualProcessor
{
  protected $indir;
  protected $outdir;

  protected $head;
  protected $foot;

  function __construct($indir, $outdir) {
    $this->indir = rtrim($indir, "/\\")."/";
    $this->outdir = rtrim($outdir, "/\\")."/";
    $this->head = file_get_contents(dirname(__FILE__).'/templates/head.tpl.html');
    $this->foot = file_get_contents(dirname(__FILE__).'/templates/foot.tpl.html');
  }

  public function run() {
    echo "Creating manual\n\n";
    $dir = new RecursiveDirectoryIterator($this->indir, RecursiveDirectoryIterator::KEY_AS_FILENAME);
    foreach ($dir->getChildren() as $file) {
      echo "Processing: $file\n";
      if ($file->isFile() && preg_match('/\.txt$/', $file->getFileName())) {
        $this->processFile($file);
      }
    }
  }

  private function processFile(DirectoryIterator $file) {
    $parser = new restructured_Parser();
    $transformer = new restructured_Transformer();
    $tree = $parser->parse(file_get_contents($file->getPathName()));
    $html = $transformer->transform($tree);
    $basename = basename($file->getFileName(), '.txt');

    if ($transformer->title) {
      $title = $transformer->title;
    } else {
      $title = ucwords(str_replace('-', ' ', $basename));
    }

    $content = str_replace('{TITLE}', htmlspecialchars($title, ENT_QUOTES), $this->head) . $html . $this->foot;
    $outfile = $this->outdir . $basename . '.html';
    file_put_contents($outfile, $content);
  }
}

$transformer = new ManualProcessor(dirname(__FILE__)."/../../docs/rst", dirname(__FILE__)."/../../docs");
$transformer->run();
