<?php $record = $this->getModel(); ?>
<h1>
  <?php e($record->title); ?>
</h1>
<span class="date"><?php e($record->published); ?></span>
<a href="<?php e(url('edit')); ?>">edit</a>
|
<a href="<?php e(url('delete')); ?>">delete</a>
<p>
  <?php e($record->content); ?>
</p>
