<?php if ($description) : ?><span class="tooltip" title="<?php echo $description; ?>"><?php endif; ?>
<?php if (class_exists($type, FALSE) || interface_exists($type, FALSE)) : ?>
<a href="#class-<?php echo strtolower($type); ?>"
><?php echo $type; ?></a>
<?php else : ?>
<span class="modifier"><?php echo $type; ?></span>
<?php endif; ?>

$<?php echo $name; ?>
<?php if ($description) : ?></span><?php endif; ?>