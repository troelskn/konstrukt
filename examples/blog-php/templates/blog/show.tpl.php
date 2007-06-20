<h1>
  <?php echo $record->title; ?>
</h1>
<span class="date"><?php echo $record->published; ?></span>
<a href="<?php echo $edit; ?>">edit</a>
|
<a href="<?php echo $delete; ?>">delete</a>
<p>
  <?php echo $record->content; ?>
</p>
