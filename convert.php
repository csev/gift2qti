<?php

session_start();
if ( !isset($_POST['text']) ) die('Missing input data');
$text =  $_POST['text'];

echo("<pre>\n");
require_once("parse.php");

if ( count($errors) == 0 ) {
    print "Conversion success\n";
} else {
    print "Conversion errors:\n";
    print_r($errors);
}

// print_r($questions);

require_once("make_qti.php");

require_once("make_zip.php");
echo("</pre>\n");
