<?php
    if (count($important) > 0) {
      echo "\n".'{rank=max; '.implode(' ', $important).';}'."\n";
    }
?>
}