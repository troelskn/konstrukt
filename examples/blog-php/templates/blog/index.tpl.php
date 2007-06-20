<?php foreach ($resultset as $result) : ?>
  <h2><a href="<?php echo $result->href; ?>"><?php echo $result->title; ?></a></h2>
  <span class="date"><?php echo $result->published; ?></span>
  <p><?php echo $result->excerpt; ?></p>
<?php endforeach; ?>

<p class="pager">
  <?php foreach ($pagination as $link) : ?>
    <a style="float:left" href="<?php echo $link->href; ?>"><?php echo $link->index; ?></a>
  <?php endforeach; ?>
  <a style="float:right" href="<?php echo $create; ?>">create</a>
</p>
