<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN'
  'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>
<html>
<head>
  <meta http-equiv="content-type" content="text/html; charset=<?php e($encoding); ?>" />
   <title><?php e($title); ?></title>
<?php foreach ($scripts as $script) : ?>
  <script type="text/javascript" src="<?php e($script); ?>"></script>
<?php endforeach; ?>
<?php foreach ($styles as $style) : ?>
  <link rel="stylesheet" type="text/css" href="<?php e($style); ?>" />
<?php endforeach; ?>
</head>
<body>
<?php echo $content; ?>
</body>
</html>