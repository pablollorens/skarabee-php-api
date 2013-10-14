<?php

/**
 * Skarabee
 *
 * This Skarabee PHP Wrapper class connects to the Skarabee SOAP API, called Weblink.
 *
 * @author Jeroen Desloovere <jeroen@siesqo.be>
 */

// require
require_once '../src/Skarabee/Skarabee.php';

// define credentials
$username = '';
$password = '';

// init api
$api = new Skarabee($username, $password);

// insert
$item = array(
	'comments' => 'dit is een test van Reclamebureau Siesqo',
	'first_name' => 'jeroen',
	'last_name' => 'desloovere'	
);

print_r($api->addContactMessage($item));
