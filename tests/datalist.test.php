<?php
require_once 'support/simpletest.inc.php';
require_once '../examples/std.inc.php';
require_once 'support/mocks.inc.php';

class MockDataListCrudeView extends k_DataList
{
  function viewHandler($tableData) {
    extract($tableData);
    ob_start();
?>
<!-- table : begin -->
<table class="datalist"
<?php if (isset($id)) : ?>
      id="<?php echo $id; ?>"
<?php endif; ?>
>
<?php if (count($head) > 0) : ?>
      <thead>
      <tr>
<?php $first = TRUE; foreach ($head as $th) : ?>
<th
<?php if ($first) : ?>
  class="first"
<?php endif; ?>
>
<a href="<?php echo htmlentities($th['href']); ?>" title="<?php echo sprintf($this->__("sort_".strtolower($th['direction'])), $this->__($th['column']))?>"><?php echo htmlentities($this->__($th['column'])); ?></a>
</th>
<?php $first = FALSE; endforeach; ?>
</tr>
</thead>
<?php endif; ?>

<tbody>
<?php $first = TRUE; foreach ($body as $row) : ?>
<tr
<?php if ($first) : ?>
  class="first"
<?php endif; ?>
>
<?php if (count($head) > 0) : ?>
<?php foreach ($head as $th) : ?>
  <td><?php echo $row[$th['column']]; ?></td>
<?php endforeach; ?>
<?php else: ?>
<?php foreach ($row as $col) : ?>
  <td><?php echo $col; ?></td>
<?php endforeach; ?>
<?php endif; ?>
<?php $first = FALSE; endforeach; ?>

</tbody>
</table>
<!-- table : end -->
<?php
    return ob_get_clean();
  }
}

class MockDataListCallBack
{
  public $log = Array();
  public $returnValue = Array(
    Array("foo1", "foo2", "foo3"),
    Array("bar1", "bar2", "bar3"),
    Array("qux1", "qux2", "qux3"),
  );

  function callHome() {
    $this->log[] = func_get_args();
    return $this->returnValue;
  }

}

class TestOfDataList extends UnitTestCase
{
  function test_invalid_callback_raise_error() {
    $list = new MockDataListCrudeView(new MockContext());
    $list->setQueryCallback(null);
    $list->setCountCallback(null);
    try {
      $list->execute();
      $this->fail('Expected exception not thrown.');
    } catch (Exception $ex) {
      if ($ex->getMessage() == "Type mismatch. \$this->count not callable") {
        $this->pass();
      } else {
        throw $ex;
      }
    }
  }

  function test_execute_calls_back() {
    $mockquery = new MockDataListCallBack();
    $mockcount = new MockDataListCallBack();
    $mockcount->returnValue = 3;

    $list = new MockDataListCrudeView(new MockContext());
    $list->setQueryCallback(Array($mockquery, 'callHome'));
    $list->setCountCallback(Array($mockcount, 'callHome'));
    $result = $list->execute();
    $this->assertEqual(count($mockquery->log), 1);
  }

  function test_two_dim_array_is_displayed_as_html_table() {
    $mockquery = new MockDataListCallBack();
    $mockcount = new MockDataListCallBack();
    $mockcount->returnValue = 3;

    $list = new MockDataListCrudeView(new MockContext());
    $list->setQueryCallback(Array($mockquery, 'callHome'));
    $list->setCountCallback(Array($mockcount, 'callHome'));
    $result = $list->execute();
    $dom = DOMDocument::loadHTML($result);
    $table = $dom->getElementsByTagName("table")->item(0);
    $trs = $table->getElementsByTagName("tr");
    if (!$this->assertEqual($trs->length, 3, "Table should have 3 rows")) {
      $this->dump($result);
    }
    foreach ($trs as $tr) {
      $tds = $tr->getElementsByTagName("td");
      $this->assertEqual($tds->length, 3, "Row should have 3 columns");
    }
  }

  function test_headers_are_displayed_in_first_row() {
    $mockquery = new MockDataListCallBack();
    $mockquery->returnValue = Array(
      Array('foo' => "foo1", 'bar' => "foo2", 'qux' => "foo3"),
    );

    $mockcount = new MockDataListCallBack();
    $mockcount->returnValue = 1;

    $list = new MockDataListCrudeView(new MockContext());
    $list->setQueryCallback(Array($mockquery, 'callHome'));
    $list->setCountCallback(Array($mockcount, 'callHome'));
    $list->setFieldNames(Array('foo','bar','qux'));
    $result = $list->execute();

    $dom = DOMDocument::loadHTML($result);
    $header = $dom->getElementsByTagName("tr")->item(0)->getElementsByTagName("th");
    if (!$this->assertEqual($header->length, 3)) {
      $this->dump($result);
    }
  }

  function test_hashed_array_uses_hash_index_to_display_values() {
    $mockquery = new MockDataListCallBack();
    $mockquery->returnValue = Array(
      Array('bar' => "foo1", 'foo' => "foo2", 'qux' => "foo3"),
    );

    $mockcount = new MockDataListCallBack();
    $mockcount->returnValue = 1;

    $list = new MockDataListCrudeView(new MockContext());
    $list->setQueryCallback(Array($mockquery, 'callHome'));
    $list->setCountCallback(Array($mockcount, 'callHome'));
    $list->setFieldNames(Array('foo','bar','qux'));
    $result = $list->execute();


    $dom = DOMDocument::loadHTML($result);
    $table = $dom->getElementsByTagName("table")->item(0);
    $tr = $table->getElementsByTagName("tr")->item(1);
    $this->assertEqual("foo2", $tr->getElementsByTagName("td")->item(0)->nodeValue);

  }

  function test_mixed_array_uses_hash_index_to_display_values() {
    $mockquery = new MockDataListCallBack();
    $mockquery->returnValue = Array(
      Array('bar' => "foo1", 'foo' => "foo2", 'qux' => "foo3"),
    );

    $mockcount = new MockDataListCallBack();
    $mockcount->returnValue = 1;

    $list = new MockDataListCrudeView(new MockContext());
    $list->setQueryCallback(Array($mockquery, 'callHome'));
    $list->setCountCallback(Array($mockcount, 'callHome'));
    $list->setFieldNames(Array('foo','bar','qux'));
    $result = $list->execute();


    $dom = DOMDocument::loadHTML($result);
    $table = $dom->getElementsByTagName("table")->item(0);
    $tr = $table->getElementsByTagName("tr")->item(1);
    $this->assertEqual("foo2", $tr->getElementsByTagName("td")->item(0)->nodeValue);
  }

  function test_rows_longer_than_head_displays_only_columns_in_head() {
    $mockquery = new MockDataListCallBack();
    $mockquery->returnValue = Array(
      Array('bar' => "foo1", 'foo' => "foo2", 'qux' => "foo3"),
    );

    $mockcount = new MockDataListCallBack();
    $mockcount->returnValue = 1;

    $list = new MockDataListCrudeView(new MockContext());
    $list->setQueryCallback(Array($mockquery, 'callHome'));
    $list->setCountCallback(Array($mockcount, 'callHome'));
    $list->setFieldNames(Array('foo','qux'));
    $result = $list->execute();

    $dom = DOMDocument::loadHTML($result);
    $table = $dom->getElementsByTagName("table")->item(0);
    $tr = $table->getElementsByTagName("tr")->item(1);
    $thead_tr = $table->getElementsByTagName("tr")->item(0);

    $this->assertEqual(2, $tr->getElementsByTagName("td")->length);

    $this->assertEqual("foo", trim($thead_tr->getElementsByTagName("th")->item(0)->nodeValue));
    $this->assertEqual("qux", trim($thead_tr->getElementsByTagName("th")->item(1)->nodeValue));
    $this->assertEqual("foo2", trim($tr->getElementsByTagName("td")->item(0)->nodeValue));
    $this->assertEqual("foo3", trim($tr->getElementsByTagName("td")->item(1)->nodeValue));
  }

  // @todo need tests of pagination
}

simpletest_autorun(__FILE__);
