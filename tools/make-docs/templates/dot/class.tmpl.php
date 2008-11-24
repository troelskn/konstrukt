<?php echo $name; ?> [
<?php if ($is_interface) : ?>
  label = "{ \<\<interface\>\>\n\N |<?php echo implode("", $methods); ?> }"
  fillcolor = "#FAF0FF"
  style="dotted,filled"
<?php else : ?>
  label = "{ \N |<?php echo implode("", $methods); ?> |<?php echo implode("", $properties); ?> }"
<?php endif; ?>
]
<?php foreach ($implements_directly as $interface) : ?>
<?php echo $name; ?>-><?php echo $interface; ?> [style="dotted"]

<?php endforeach; ?>
<?php if ($extends) : ?>
<?php echo $name; ?>-><?php echo $extends; ?>

<?php endif; ?>

<?php foreach ($this->__global_hack as $aggregate) : ?>
<?php echo $aggregate; ?>-><?php echo $name; ?> [arrowhead="odiamond"]

<?php endforeach; ?>