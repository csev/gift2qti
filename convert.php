<?php

session_start();
unset($_SESSION['quiz']);
if ( !isset($_POST['text']) ) die('Missing input data');
$text =  $_POST['text'];

echo("<pre>\n");
require_once("parse.php");

if ( count($errors) == 0 ) {
    print "Initial parse of GIFT data successful\n";
} else {
    print "Errors in the GIFT data\n";
    print_r($errors);
}
echo("\nCreating and validating the quiz XML....\n");
flush();

// print_r($questions);

require_once("make_qti.php");
if ( !isset($DOM) ) die("Conversion not completed");

$_SESSION['quiz'] = $DOM->saveXML();

?>
Conversion complete...

</pre>
<p>
<a href="viewxml.php" target="_blank">View Quiz XML</a> |
<a href="getzip.php" target="_blank">Download ZIP</a> 
</p>
<p>
To upload to an LMS choose the ZIP format - it makes a small IMS Common Cartridge
with just the quiz in it - and it is what most LMS systems prefer to import (i.e.
they don't want you to upload the XML directly).
</p>


