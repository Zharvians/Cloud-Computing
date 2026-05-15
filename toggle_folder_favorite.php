<?php

require 'config.php';

$id = (int)$_GET['id'];

$conn->query("
    UPDATE folders
    SET is_favorite = 1 - is_favorite
    WHERE id = $id
");

header("Location:index.php");