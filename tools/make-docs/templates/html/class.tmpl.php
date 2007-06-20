<div class="class">
<h2 id="class-<?php echo strtolower($name); ?>">
<?php if (!$is_interface) : ?>
<?php if ($is_abstract) : ?>
  <em class="modifier">abstract</em>

<?php endif; ?>
<?php if ($is_final) : ?>
  <em class="modifier">final</em>

<?php endif; ?>
<?php endif; ?>
  <em><?php echo $type; ?></em>
  <a href="#class-<?php echo strtolower($name); ?>"><?php echo $name; ?></a>
<?php if ($extends) : ?>

  <em>extends</em>
  <a href="#class-<?php echo strtolower($extends); ?>"><?php echo $extends; ?></a>
<?php endif; ?>
</h2>
<?php if ($implements) : ?>
<h3>
  <em>implements</em>
<?php $first = TRUE; foreach ($implements as $interface) : ?>
<?php if (!$first) echo ","; ?>
  <a href="#class-<?php echo strtolower($interface); ?>"><?php echo $interface; ?></a><?php $first = FALSE; endforeach; ?>
</h3>
<?php endif; ?>

<?php if ($documentation) : ?>
<div class="docblock">
  <?php echo $documentation; ?>
</div>
<?php endif; ?>

<?php if ($properties) : ?>
<h3>properties</h3>
<div class="properties">
<?php foreach ($properties as $property) : ?>
  <?php echo $property; ?>

<?php endforeach; ?>
</div>
<?php endif; ?>

<?php if ($methods) : ?>
<h3>methods</h3>
<div class="methods">
<?php foreach ($methods as $method) : ?>
  <?php echo $method; ?>

<?php endforeach; ?>
</div>
<?php endif; ?>
</div>