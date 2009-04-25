<html>
  <head>
<title><?php e($title); ?></title>
<?php foreach ($styles as $style): ?>
    <link rel="stylesheet" href="<?php e($style); ?>" />
<?php endforeach; ?>
<?php foreach ($scripts as $script): ?>
    <script type="text/javascript" src="<?php e($script); ?>"></script>
<?php endforeach; ?>
  </head>
  <body>
    <?php echo $content; ?>
  </body>
<?php foreach ($onload as $javascript): ?>
    <script type="text/javascript">
      <?php echo $javascript; ?>
    </script>
<?php endforeach; ?>
</html>
