<?php
    include 'mysqldb.php';
    $db = new MyDB();

    if(isset($_POST['campo']))
    { 
        $sql = "UPDATE t" . $_POST['id'] . " SET " . $_POST['campo'] . " = '" . $_POST['valor'] . 
            "' WHERE Dia ='" . $_POST['where'] . "'";
        $db->execSQL($sql);      
    }
?>