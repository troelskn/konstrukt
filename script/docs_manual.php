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
    $content = str_replace('{TOC}', $this->generateToc($content), $content);
    $outfile = $this->outdir . $basename . '.html';
    file_put_contents($outfile, $content);
  }

  protected function generateToc($content) {
    preg_match_all('~<h([0-9]{1}) id="([^"]+)">([^<]+)</h[0-9]{1}>~', $content, $mm);
    $root = new StdClass();
    $root->parent = null;
    $root->children = array();
    $state = $root;
    $last = null;
    for ($ii=0, $ll=count($mm[0]); $ii < $ll; ++$ii) {
      $entry = new StdClass();
      $entry->level = $mm[1][$ii];
      $entry->id = $mm[2][$ii];
      $entry->title = $mm[3][$ii];
      $entry->children = array();
      if ($last && $last->level < $entry->level) {
        $state = $last;
      }
      while ($state->level >= $entry->level) {
        $state = $state->parent;
      }
      $entry->parent = $state;
      $state->children[] = $entry;
      $last = $entry;
    }
    return $this->renderList($root, ' id="table-of-contents"');
  }

  protected function renderList($node, $attributes = "") {
    $html = "<ul$attributes>\n";
    foreach ($node->children as $child) {
      $html .= '<li><a href="#'.htmlspecialchars($child->id).'">'.htmlspecialchars($child->title).'</a>';
      if (count($child->children) > 0) {
        $html .= $this->renderList($child);
      }
      $html .= '</li>' . "\n";
    }
    $html .= '</ul>' . "\n";
    return $html;
  }
}

$transformer = new ManualProcessor(getcwd()."/docs/txt", getcwd()."/docs");
$transformer->run();
