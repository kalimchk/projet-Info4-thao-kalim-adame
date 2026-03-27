<?php
$pdo = new PDO("mysql:host=localhost;dbname=pasta_la_vista", "root", "");
$pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
?>