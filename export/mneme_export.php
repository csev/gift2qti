<?php
/* Mneme seems to export QTI 2.x with everything very 
   HTMLEntity encoded rather than HTML in XML encoded.
   So we just kind of bank it to get rid of the non-essential
   cruft and all unused tags and leave a pretty reasonable 
   hand-editing job to get it into GIFT.   This is *not*
   even close to automatic. */

$files = scandir('.');
foreach ($files as $file ) {
    if ( strpos($file,"question") === false ) continue;
    if ( strpos($file,".xml") === false ) continue;
    // echo($file."\n");
    $data = file_get_contents($file);
    // echo(strlen($data)."\n");
    $data = preg_replace(
        array("/<itemBody.*?>/","/<\\/itembody>/",
            "/<correctResponse.*?>/","/<\\/correctResponse>/",
            "/<simpleChoice.*?>/","/<\\/simpleChoice>/"),
        array("\n::Q01::", "\n", "\n", "\n", "\n", "\n"),
        $data);
    // echo($data."\n");
    $data = preg_replace("/<.*?>/","",$data);
    $data = str_replace(
        array("&quot;", "&ldquo;", "&rdquo;", "&nbsp;", "&lt;", "&gt;", "&hellip;", "&#39;"),
        array("'", "\"", "\"", " ", "<", ">", "...", "'"),
        $data);
    $data = str_replace("&amp;","&",$data);
    $data = str_replace("\n\n","\n",$data);
    $data = str_replace("\n\n","\n",$data);
    $data = str_replace("\n\n","\n",$data);
    echo($data."\n");
    
}
