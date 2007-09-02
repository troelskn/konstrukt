<!-- table : begin -->
<table class="datalist"
<?php if (isset($id)) : ?>
      id="<?php e($id); ?>"
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
<a href="<?php e($th['href']); ?>" title="<?php printf($this->__("sort_".strtolower($th['direction'])), $this->__($th['column']))?>"><?php e($this->__($th['column'])); ?></a>
<?php if ($th['selected']) : ?>
<?php if ($this->state->get('direction') == 'ASC') : ?>
            &or;
<?php else: ?>
            &and;
<?php endif; ?>
<?php endif; ?>
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
  <td><?php e($row[$th['column']]); ?></td>
<?php endforeach; ?>
<?php else: ?>
<?php foreach ($row as $col) : ?>
  <td><?php e($col); ?></td>
<?php endforeach; ?>
<?php endif; ?>
<?php $first = FALSE; endforeach; ?>

</tbody>
</table>
<!-- table : end -->
<?php if ($this->showPager) : ?>
<?php extract($pager); ?>
<!-- pager : begin -->
<table class="pager">
<tr>
  <td style="text-align:left"><?php e($this->__('page')); ?> :
<?php

$flag = false;
foreach ($sections as $section) {
  if ($flag) {
    e(" ... ");
  }
  $flag = true;
  foreach ($section as $link) {
    if ($link['current_page']) {
      echo "<span class=\"current\">".htmlentities($link['title'])."</span> ";
    } else {
      echo "<a href=\"".htmlentities($link['href'])."\">".htmlentities($link['title'])."</a>";
    }
    if (!$link['lastlink']) {
      echo " | ";
    }
  }
}
?>
  </td>
  <td style="text-align:center">
  (<?php e($item_count); ?> <?php e($this->__('results')); ?>)
  </td>
  <td style="text-align:right">
<?php
if (is_null($prevpage) && is_null($nextpage)) echo "&nbsp;";
// prev / next
if (!is_null($prevpage)) {
  echo "<a href=\"".htmlentities($prevpage['href'])."\">".$this->__('prev_page')."</a>";
}
if (!is_null($nextpage)) {
  if (!is_null($prevpage)) echo " | ";
  echo "<a href=\"".htmlentities($nextpage['href'])."\">".$this->__('next_page')."</a>";
}
?>
  </td>
</tr>
</table>
<!-- pager : end -->

<?php endif; ?>
