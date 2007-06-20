<?php
if ($is_public) {
  echo "+ ";
}
if ($is_protected) {
  echo "# ";
}
if ($is_private) {
  echo "- ";
}
echo $name;
echo " : ";
echo $var;
echo "\\l";
if (!$is_inherited && (class_exists($var, FALSE) || interface_exists($var, FALSE))) {
  $this->__global_hack[] = $var;
}
