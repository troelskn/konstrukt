<!DOCTYPE html PUBLIC '-//W3C//DTD XHTML 1.0 Strict//EN'
  'http://www.w3.org/TR/xhtml1/DTD/xhtml1-strict.dtd'>
<html>
<head>
  <meta http-equiv="content-type" content="text/html; charset=<?php echo $encoding; ?>" />
  <title><?php echo $title; ?></title>
<?php foreach ($scripts as $script) : ?>
  <script type="text/javascript" src="<?php echo $script; ?>"></script>
<?php endforeach; ?>
<?php foreach ($styles as $style) : ?>
  <link rel="stylesheet" type="text/css" href="<?php echo $style; ?>" />
<?php endforeach; ?>
</head>
<body>
<?php echo $content; ?>
</body>
</html>