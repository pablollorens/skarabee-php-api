<?php

/**
 * Skarabee
 *
 * This Skarabee PHP Wrapper class connects to the Skarabee SOAP API, called Weblink.
 *
 * @author Jeroen Desloovere <info@jeroendesloovere.be>
 */

// add your own credentials in this file
require_once __DIR__ . '/credentials.php';

// required to load
require_once __DIR__ . '/../src/Skarabee.php';

use JeroenDesloovere\Skarabee\Skarabee;

// init api
$api = new Skarabee($username, $password);

// get publication
//$items = $api->get($publicationID);

// get all publications
$items = $api->getAll();

// get all projects
//$items = $api->getAllProjects();

// get contact info
//$item = $api->getContactInfo();

// insert
/*$item = array(
    'comments' => 'dit is een test van Reclamebureau Siesqo',
    'first_name' => 'jeroen-API',
    'last_name' => 'desloovere',
    'phone' => '000000000'
);

print_r($api->addContactMessage($item));
*/

print_r($items);

// send feedback
$result = $api->pingBack(2247560, 'AVAILABLE', 'Dit is een test-feedback', '1', 'http://www.eribo.be');

print_r($result);
