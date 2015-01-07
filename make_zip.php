<?php

date_default_timezone_set('UTC');

$DOM = new DOMDocument('1.0');
$DOM->preserveWhiteSpace = false;
$DOM->formatOutput = true;
$DOM->loadXML($QTI->asXML());
$pretty_print = $DOM->saveXML();
echo "\nValidating (may take a few seconds)...\n";
libxml_use_internal_errors(true);
if ( ! $DOM->schemaValidate('xml/ims_qtiasiv1p2p1.xsd') ) {
    echo "\nWarning: Quiz XML Not Valid\n";
    $errors = libxml_get_errors();
    foreach ($errors as $error) {
        echo "Error:", libxml_display_error($error), "\n";
    }
    libxml_clear_errors();
}

unlink("quiz.zip");
echo "Making a ZIP\n";
$zip = new ZipArchive();
if ($zip->open("quiz.zip", ZipArchive::CREATE)!==TRUE) {
    exit("cannot open <quiz.zip>\n");
}

// Stuff we substitute...
$quiz_id = 'i'.$uuid;
$today = date('Y-m-d');
$ref_id = 'r'.uniqid();
$manifest_id = 'm'.uniqid();
$title = "Title goes here";
$desc = "Description goes here";
$source = array("__DATE__", "__QUIZ_ID__","__REF_ID__", "__TITLE__","__DESCRIPTION__", "__MANIFEST_ID__");
$dest = array($today, $quiz_id, $ref_id, $title, $desc, $manifest_id);

// Add the ims Manifest
$manifest = str_replace($source, $dest, file_get_contents('xml/imsmanifest.xml'));
$zip->addFromString('imsmanifest.xml',$manifest);

// Add the Assessment Metadata
$meta = str_replace($source, $dest, file_get_contents('xml/assessment_meta.xml'));
$zip->addFromString($quiz_id.'/assessment_meta.xml',$meta);

// Add the quiz
$zip->addFromString($quiz_id.'/'.$quiz_id.'.xml',$DOM->saveXML());

// $zip->addFile($thisdir . "/too.php","/testfromfile.php");
echo "numfiles: " . $zip->numFiles . "\n";
echo "status:" . $zip->status . "\n";

$zip->close();

