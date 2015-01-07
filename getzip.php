<?php
date_default_timezone_set('UTC');
session_start();
if ( !isset($_SESSION['quiz']) ) die('Missing quiz data');
// Stuff we substitute...
$quiz_id = 'i'.uniqid();
$today = date('Y-m-d');
$ref_id = 'r'.uniqid();
$manifest_id = 'm'.uniqid();
$title = "Title goes here";
$desc = "Description goes here";
$source = array("__DATE__", "__QUIZ_ID__","__REF_ID__", "__TITLE__","__DESCRIPTION__", "__MANIFEST_ID__");
$dest = array($today, $quiz_id, $ref_id, $title, $desc, $manifest_id);

// here we go...
$filename = tempnam(sys_get_temp_dir(), $quiz_id.".zip");
$filename = tempnam(sys_get_temp_dir(), "abc123.zip");
$zip = new ZipArchive();
if ($zip->open($filename, ZipArchive::CREATE)!==TRUE) {
    die("cannot open <quiz.zip>\n");
}

header( "Content-Type: application/x-zip" );
header( "Content-Disposition: attachment; filename=\"$quiz_id.zip\"" );

// Add the ims Manifest
$manifest = str_replace($source, $dest, file_get_contents('xml/imsmanifest.xml'));
$zip->addFromString('imsmanifest.xml',$manifest);

// Add the Assessment Metadata
$meta = str_replace($source, $dest, file_get_contents('xml/assessment_meta.xml'));
$zip->addFromString($quiz_id.'/assessment_meta.xml',$meta);

// Add the quiz
$zip->addFromString($quiz_id.'/'.$quiz_id.'.xml',$_SESSION['quiz']);

$zip->close();
readfile($filename);
// unlink($filename);



