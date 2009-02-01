<?php if ($pages->pageCount): ?>
<table>
  <thead>
    <tr>
<?php foreach ($keys as $key): ?>
      <th><a href="<?php e(url('', array('sort' => $key, 'order' => $order_asc && ($sort == $key) ? 'desc' : 'asc'))); ?>"><?php e($key); ?></a></th>
<?php endforeach; ?>
    </tr>
  </thead>
  <tbody>
<?php foreach ($collection as $row): ?>
    <tr>
<?php foreach ($row as $value): ?>
      <td><?php e($value); ?></td>
<?php endforeach; ?>
    </tr>
<?php endforeach; ?>
  </tbody>
</table>
<?php endif; ?>

<!--See http://developer.yahoo.com/ypatterns/pattern.php?pattern=searchpagination-->
<?php if ($pages->pageCount): ?>
<div class="paginationControl">
<!-- Previous page link -->
<?php if (isset($pages->previous)): ?>
  <a href="<?php e(url('', array('page' => $pages->previous))); ?>">
    &lt; Previous
  </a> |
<?php else: ?>
  <span class="disabled">&lt; Previous</span> |
<?php endif; ?>
<!-- Numbered page links -->
<?php foreach ($pages->pagesInRange as $page): ?>
  <?php if ($page != $pages->current): ?>
    <a href="<?php e(url('', array('page' => $page))); ?>">
        <?php e($page); ?>
    </a> |  <?php else: ?>
    <?php e($page); ?> |
  <?php endif; ?>
<?php endforeach; ?>
<!-- Next page link -->
<?php if (isset($pages->next)): ?>
  <a href="<?php e(url('', array('page' => $pages->next))); ?>">
    Next &gt;  </a>
<?php else: ?>
  <span class="disabled">Next &gt;</span>
<?php endif; ?>
</div>
<?php endif; ?>

