<?php foreach ($resultset as $result) : ?>
  <h2><a href="<?php e(url($result->name)); ?>"><?php e($result->title); ?></a></h2>
  <span class="date"><?php e($result->published); ?></span>
  <p><?php e($result->excerpt); ?></p>
<?php endforeach; ?>

<p class="pager">
  <?php foreach ($pagination as $link) : ?>
    <a style="float:left" href="<?php e($link->href); ?>"><?php e($link->index); ?></a>
  <?php endforeach; ?>
  <a style="float:right" href="<?php e(url('create')); ?>">create</a>
</p>
