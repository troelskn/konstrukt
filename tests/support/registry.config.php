<?php
$constructors = Array(
  "TestOfRegistry_Foo" => create_function(
    '$className, $args, $registry',
    'return new StdClass();'
  )
);
$aliases = Array(
  "yabba_the_hutt" => "TestOfRegistry_Foo"
);
