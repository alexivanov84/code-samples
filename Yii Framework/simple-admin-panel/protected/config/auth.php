<?php 
$roles = array(

  'admin' => 
  array (
    'type' => CAuthItem::TYPE_ROLE,
    'description' => '',
    'bizRule' => NULL,
    'data' => NULL,
    'children' => 
    array (),
  ),

  'editor' => 
  array (
    'type' => CAuthItem::TYPE_ROLE,
    'description' => '',
    'bizRule' => NULL,
    'data' => NULL,
    'children' => 
    array (),
  ),
  
);
$operations = array(

);

return array_merge($operations, $roles);

