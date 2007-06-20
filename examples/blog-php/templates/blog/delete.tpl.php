<form <?php echo $this->getFormProperties(); ?>>
<?php if (count($this->fields->getMessages()) > 0) : ?>
  <p class="error">
    <?php echo implode("<br>", $this->fields->getMessages()); ?>
  </p>
<?php endif; ?>
</p>
  <p>
    <input type="submit" value="Delete this post ?" />
  </p>
</form>
