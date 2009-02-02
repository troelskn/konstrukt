<?php
require_once '../../lib/konstrukt/konstrukt.inc.php';

// You must have ZendFramework on your path
require_once 'Zend/Paginator.php';
require_once 'Zend/Paginator/Adapter/Iterator.php';
require_once 'Zend/View.php';
require_once 'dummy_collection.inc.php';

/**
 * This example shows how to integrate ZF Paginator, to create a pageable table-view of a resultset.
 * For this example, A dummy is used as the model, but you would likely replace it with a
 * lazy-loading wrapper, that queries the underlying database for the required items.
 */
class ZfPaginationPage extends k_Component {
  protected $template = 'list.tpl.php';
  protected $paginator;
  protected $collection;
  protected $url_init = array(
    'page' => 0,
    'sort' => null,
    'order' => 'asc');
  /**
   * Returns a collection, able to resolve into Countable, SeekableIterator
   */
  protected function createSelection($sort_by, $ascending) {
    return new DummyCollection($sort_by, $ascending);
  }
  protected function getKeys() {
    return $this->getSelection()->getKeys();
  }
  protected function getSelection() {
    if (!isset($this->collection)) {
      $this->collection = $this->createSelection($this->query('sort'), $this->query('order') == 'asc');
    }
    return $this->collection;
  }
  protected function getPaginator() {
    if (!isset($this->paginator)) {
      $this->paginator = new Zend_Paginator(new Zend_Paginator_Adapter_Iterator($this->getSelection()));
      $this->paginator->setCurrentPageNumber($this->query('page'));
      $this->paginator->setItemCountPerPage(10);
    }
    return $this->paginator;
  }
  function GET() {
    $t = new k_Template('templates/' . $this->template);
    return $t->render(
      $this,
      array(
        'collection' => $this->getPaginator(),
        'order_asc' => $this->query('order') == 'asc',
        'sort' => $this->query('sort'),
        'keys' => $this->getKeys(),
        'pages' => $this->getPaginator()->getPages()));
  }
}

if (realpath($_SERVER['SCRIPT_FILENAME']) == __FILE__) {
  k()->run('ZfPaginationPage')->out();
}
