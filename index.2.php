<?php
include 'functions.php';

  $db = new MyDB();  

  $db->createTableAnuncios();

  $ret = $db->execSQLQuery('select configuracoes from anuncios where configuracoes is not null');    

  while($row = $ret->fetchArray())
  {
    $config = json_decode($row[0], true);
    $i=0;
  }           
  
  $db->close();
  
?>

