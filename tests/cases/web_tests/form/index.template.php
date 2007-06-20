<form <?php echo $this->getFormProperties(); ?>>
<p>
<label for="<?php echo $this->fields->foo->id; ?>">foo</label>
<?php echo $this->fields->foo; ?>
<?php foreach ($this->fields->foo->messages as $error) : ?>
<span><?php echo $error; ?></span>
<?php endforeach; ?>
</p>

<?php foreach ($this->fields->getExclusive("foo") as $field) : ?>
<?php if ($field->id != "foo"): ?>
<p>
<label for="<?php echo $field->id; ?>"><?php echo $field->name; ?></label>
<?php echo $field; ?>
<?php foreach ($field->messages as $error) : ?>
<span><?php echo $error; ?></span>
<?php endforeach; ?>

</p>
<?php endif; ?>
<?php endforeach; ?>

<input type="submit" />
</form>