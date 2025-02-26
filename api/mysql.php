<?php

$mysqli = new mysqli("localhost", $dbuser, $dbpasswd, $dbname);
if ($mysqli->connect_errno) {
    die("Connect Error: " . $mysqli->connect_error);
}
