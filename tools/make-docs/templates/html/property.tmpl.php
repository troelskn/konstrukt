<h4 id="class-<?php echo strtolower($class); ?>-property-<?php echo strtolower($name); ?>">
<?php if ($is_public) : ?>
  <em class="modifier">public</em>

<?php endif; ?>
<?php if ($is_protected) : ?>
  <em class="modifier">protected</em>

<?php endif; ?>
<?php if ($is_private) : ?>
  <em class="modifier">private</em>

<?php endif; ?>

  $<?php echo $name; ?>

<?php if (class_exists($var, FALSE) || interface_exists($var, FALSE)) : ?>
  : <em class="modifier"><a href="#class-<?php echo strtolower($var); ?>"><?php echo $var; ?></a></em>

<?php else : ?>
  : <em class="modifier"><?php echo $var; ?></em>

<?php endif; ?>
</h4>

<?php if ($documentation) : ?>
<div class="docblock">
  <?php echo $documentation; ?>
</div>
<?php endif; ?>