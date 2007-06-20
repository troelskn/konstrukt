<?php
/*
if ($is_static) {
  echo "s";
}
if ($is_abstract) {
  echo "a";
}
if ($is_final) {
  echo "f";
}
*/
if ($is_public) {
  echo "+";
}
if ($is_protected) {
  echo "#";
}
if ($is_private) {
  echo "-";
}
echo " ";
echo $name;
echo "(";
echo implode(", ", array_keys($parameters));
echo ")";
echo " : ";
echo @$return;
echo "\\l";
