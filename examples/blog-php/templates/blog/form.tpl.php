<form <?php echo $this->getFormProperties(); ?>>
<?php if (count($this->fields->getMessages()) > 0) : ?>
  <p class="error">
    <?php echo implode("<br/>", array_map('e', $this->fields->getMessages())); ?>
  </p>
<?php endif; ?>
<?php foreach ($this->fields as $field) : ?>
<p>
  <label for="<?php e($field->id); ?>"><?php e($field->name); ?></label>
<?php echo $field; ?>
<?php if (!empty($field->messages)) : ?>
  <br />
  <span class="error">
    <?php e(implode(", ", $field->messages)); ?>
  </span>
<?php endif; ?>

</p>
<?php endforeach; ?>

<p>
  <input type="submit" />
</p>
</form>
