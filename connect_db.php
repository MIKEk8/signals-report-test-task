<?php
include_once(__DIR__ . '/config.php');

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

$mysqli = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
$mysqli->set_charset('utf8mb4');
