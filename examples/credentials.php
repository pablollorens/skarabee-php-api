<?php

// define own skarabee credentials
$username = ''; // required
$password = ''; // required

// username and password are required
if (empty($username) || empty($password)) {
    echo 'Please define your username and password in ' . __DIR__ . '/credentials.php';
}
