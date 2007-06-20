<?php
/**
  * The Datalist is a standard component, for providing a list of
  * results. In addition to sort options, it provides pagination.
  * The view state is carried over queryParams (GET-method).
  *
  * The main interface is provided through two callbacks; [[query|#class-k_datalist-property-query]]
  * and [[count|#class-k_datalist-property-count]], which are set by
  * their respective setter methods.
  *
  * Further customization can happen by setting public properties and the
  * fieldnames property.
  */
class k_DataList extends k_Component
{
  /**
    * @var callback
    */
  protected $query;
  /**
    * @var callback
    */
  protected $count;
  /**
    * @var array
    */
  protected $fieldNames = Array();

  public $template = "../templates/datalist.tpl.php";

  public $offset = 0;
  public $order = NULL;
  public $direction = 'ASC';

  public $pageSize = 10;

  public $showPager = TRUE;

  /**
    * Sets the callback for querying results. The callback must be a valid
    * callback, and will be invoked with the following arguments:
    *
    * ``offset``, ``limit``, ``order-by``, and ``direction``
    *
    * These arguments maps directly to the ``limit`` and ``order by``
    * clauses of a mysql select query.
    *
    * @param  callback  $callback  A callback, which returns a list of results.
    */
  function setQueryCallback($callback) {
    $this->query = $callback;
  }

  /**
    * Sets the callback for counting all results.
    *
    * The value is used for calculating pagination. It can be implemented as
    * a ``count(*)`` corresponding to the actual query.
    *
    * @param  callback  $callback  A callback, which returns the number of results.
    */
  function setCountCallback($callback) {
    $this->count = $callback;
  }

  /**
    * Sets the fields which should be displayed from the resultset.
    *
    * @param  array  $fieldNames  An array of strings
    */
  function setFieldNames($fieldNames) {
    $this->fieldNames = $fieldNames;
  }

  /**
    * The handler for rendering view.
    *
    * @param   array   $tableData   An associative array of view-data for rendering the view.
    * @return string
    */
  function viewHandler($tableData) {
    return $this->render($this->template, $tableData);
  }

  function execute() {
    $this->loadState();
    if (!is_callable($this->count)) {
      throw new Exception("Type mismatch. \$this->count not callable");
    }
    if (!is_callable($this->query)) {
      throw new Exception("Type mismatch. \$this->query not callable");
    }

    if ($this->offset < 0) {
      $this->offset = ceil($count / $this->pageSize) + $this->offset;
      if ($this->offset < 0) {
        $this->offset = 0;
      }
    }

    $offset = $this->offset * $this->pageSize;
    $result = call_user_func($this->query, $offset, $this->pageSize, $this->order, $this->direction);
    if (!is_array($result)) {
      return $this->__("empty");
    }

    $count = call_user_func($this->count);
    if ($count == 0) {
      return $this->__("empty");
    }
    $pager = $this->calculatePagination($this->offset, $count, $this->pageSize);

    $head = Array();
    foreach ($this->fieldNames as $column) {
      $th = Array();
      $th['column'] = $column;
      $th['direction'] = ($this->order == $column && $this->direction == 'ASC') ? 'DESC' : 'ASC';
      $th['href'] = $this->url("", Array('order' => $column, 'direction' => $th['direction']));
      $th['selected'] = $this->order == $column;
      $head[] = $th;
    }
    $tableData = Array(
      'id' => isset($this->id) ? $this->id : uniqid("autoid-"),
      'head' => $head,
      'body' => $result,
      'pager' => $pager,
    );
    return $this->viewHandler($tableData);
  }

  protected function loadState() {
    foreach (Array('offset', 'order', 'direction') as $param) {
      if (isset($this->GET[$param])) {
        $this->$param = $this->GET[$param];
      }
      $this->direction = strtoupper($this->direction);
    }
  }

  protected function calculatePagination($show_page, $item_count, $limit = 10) {
    $viewData = Array();
    $viewData['limit'] = $limit;
    if ($viewData['limit'] == 0) {
      throw new Exception("items per page can't be 0");
    }

    $viewData['item_count'] = $item_count;
    $viewData['show_page'] = $show_page;
    if (($viewData['show_page'] * $viewData['limit']) >= $viewData['item_count']) {
      $viewData['show_page'] = floor($viewData['item_count'] / $viewData['limit'])-1;
    }
    if ($viewData['show_page'] < 0) {
      $viewData['show_page'] = 0;
    }

    $viewData['offset'] = $viewData['show_page'] * $viewData['limit'];

    $hele_sider = floor($viewData['item_count'] / $viewData['limit']);
    if ($viewData['item_count'] > ($hele_sider * $viewData['limit'])) {
      $viewData['page_count'] = $hele_sider + 1;
    } else {
      $viewData['page_count'] = $hele_sider;
    }
    $viewData['item_count_low'] = (($viewData['show_page'] * $viewData['limit']) + 1);
    $viewData['item_count_high'] = (($viewData['show_page'] + 1) * $viewData['limit']);
    if ($viewData['item_count_high'] > $viewData['item_count']) {
      $viewData['item_count_high'] = $viewData['item_count'];
    }
    if ($viewData['item_count_high'] == 0) {
      $viewData['item_count_low'] = 0;
    }


    if ($viewData['show_page'] > 0) {
      $viewData['prevpage'] = Array('lastlink' => ($viewData['show_page'] < ($viewData['page_count'] - 1)), 'show_page' => $viewData['show_page'] - 1);
      $viewData['prevpage']['href'] = $this->url("", Array('offset' => $viewData['prevpage']['show_page']));
    } else {
      $viewData['prevpage'] = null;
    }
    if ($viewData['show_page'] < ($viewData['page_count'] - 1)) {
      $viewData['nextpage'] = Array('lastlink' => null, 'show_page' => $viewData['show_page'] + 1);
      $viewData['nextpage']['href'] = $this->url("", Array('offset' => $viewData['nextpage']['show_page']));
    } else {
      $viewData['nextpage'] = null;
    }

    $viewData['sections'] = Array();
    $paging = Array();
    for ($n=0; $n < $viewData['page_count']; $n++) {
      if ($n < 3 || ($n > $viewData['show_page'] - 2 && $n < $viewData['show_page'] + 2) || $n >= $viewData['page_count'] - 3) {
        $link_or_last = !($n+1 < 3 || ($n+1 > $viewData['show_page']-2 && $n+1 < $viewData['show_page']+2) || $n+1 >= $viewData['page_count']-3);
        if ($n == $viewData['page_count']-1) {
          $link_or_last = true;
        }

        $tmp = Array('lastlink' => $link_or_last, 'current_page' => ($n == $viewData['show_page']), 'show_page' => $n, 'title' => ($n+1));
        $tmp['href'] = $this->url("", Array('offset' => $tmp['show_page']));

        array_push($paging, $tmp);
      } else if (count($paging) > 0) {
        array_push($viewData['sections'], $paging);
        $paging = Array();
      }
    }
    if (count($paging) > 0) {
      array_push($viewData['sections'], $paging);
    }
    return $viewData;
  }
}
