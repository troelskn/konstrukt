<?php if ($deprecated) : ?>
<div class="deprecated">
<?php endif; ?>


<h4 id="class-<?php echo strtolower($class); ?>-method-<?php echo strtolower($name); ?>">

<?php if ($is_static) : ?>
  <em class="modifier">static</em>,

<?php endif; ?>
<?php if ($is_abstract) : ?>
  <em class="modifier">abstract</em>,

<?php endif; ?>
<?php if ($is_final) : ?>
  <em class="modifier">final</em>,

<?php endif; ?>
<?php if ($is_public) : ?>
  <em class="modifier">public</em>

<?php endif; ?>
<?php if ($is_protected) : ?>
  <em class="modifier">protected</em>

<?php endif; ?>
<?php if ($is_private) : ?>
  <em class="modifier">private</em>

<?php endif; ?>

  <?php echo $name; ?>(<?php if ($parameters_concat): ?><em><?php echo $parameters_concat; ?></em><?php endif; ?>)

<?php if (class_exists($return, FALSE) || interface_exists($return, FALSE)) : ?>
  : <em class="modifier"><a href="#class-<?php echo strtolower($return); ?>"><?php echo $return; ?></a></em>

<?php else : ?>
  : <em class="modifier"><?php echo $return; ?></em>

<?php endif; ?>

</h4>

<?php if (isset($todo)) : ?>
<p class="todo">
  <strong>todo</strong>
  <br />
  <?php echo implode("", $todo); ?>
</p>
<?php endif; ?>
<?php if ($documentation) : ?>
<div class="docblock">
  <?php echo $documentation; ?>
</div>
<?php endif; ?>

<?php if ($deprecated) : ?>
</div>
<?php endif; ?>