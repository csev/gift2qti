<?php

$text = 
"// true/false
::Q1:: 1+1=2 {T}

// multiple choice with specified feedback for right and wrong answers
::Q2:: What's between orange and green in the spectrum? 
{ =yellow # right; good! ~red # wrong, it's yellow ~blue # wrong, it's yellow }

// fill-in-the-blank
::Q3:: Two plus {=two =2} equals four.

// matching
::Q4:: Which animal eats which food? { =cat -> cat food =dog -> dog food }

// math range question
::Q5:: What is a number from 1 to 5? {#3:2}

// math range specified with interval end points
::Q6:: What is a number from 1 to 5? {#1..5}
// translated on import to the same as Q5, but unavailable from Moodle question interface

// multiple numeric answers with partial credit and feedback
::Q7:: When was Ulysses S. Grant born? {#
         =1822:0      # Correct! Full credit.
         =%50%1822:2  # He was born in 1822. Half credit for being close.
}

// essay
::Q8:: How are you? {}
";

$errors = [];
$raw_questions = array();
$question = "";
$lines = explode("\n", $text);
foreach ( $lines as $line ) {
    $line = rtrim($line);
    if ( strpos($line, "//") === 0 ) continue;
    if ($line == "" ) {
        if ( strlen($question) > 0 ) {
            $raw_questions[] = $question;
            $question = "";
        }
        continue;
    }
    if ( strlen($question) > 0 ) $question .= " ";
    $question .= trim($line);
}

$questions = array();
foreach ( $raw_questions as $raw ) {
    $pieces = explode('::', $raw);
    if ( count($pieces) != 3 ) {
        $errors[] = "Mal-formed question: ".$raw;
        continue;
    }
    // print_r($pieces);
    $name = trim($pieces[1]);
    $text = trim($pieces[2]);
    $spos = strpos($text,'{');
    $epos = strpos($text,'}', $spos);
    // echo $spos, " ", $epos, "\n";
    if ( $spos < 1 || $epos < 1 ) {
        $errors[] = "Could not find answer: ".$raw;
        continue;
    }
    $answer = trim(substr($text,$spos+1, $epos-$spos-1));
    if ( $epos == strlen($text)-1 ) {
        $question = trim(substr($text,0,$spos-1));
        $type = 'unknown';
    } else {
        $question = trim(substr($text,0,$spos-1)) . " [_____] " . trim(substr($text,$epos+1));
        $type = 'short_answer_question';
    }

    if ( $type == 'short_answer_question' ) {
        // We are good...
    } else if ( strpos($answer,"T") === 0 || strpos($answer, "F") === 0 ) {
        $type = 'true_false_question';
    } else if ( strlen($answer) < 1 ) {
        $type = 'essay_question';
    } else if ( strpos($answer, '#') === 0 ) {
        $type = 'numerical_question';
        $errors[] = "Numerical questions not yet supported: ".$raw;
        continue;
    }  else if ( strpos($answer,"=") === 0 || strpos($answer, "~") === 0 ) {
        $type = 'multiple_choice_question';
    } else { 
        $errors[] = "Could not determine question type: ".$raw;
        continue;
    }
    $answers = [];
    $parsed_answer = false;
    if ( $type == 'short_answer_question' || $type == 'multiple_choice_question') {
        $parsed_answer = [];
        $correct = null;
        $answer_text = false;
        $feedback = false;
        $in_feedback = false;
        for($i=0;$i<strlen($answer)+1; $i++ ) {
            $ch = $i < strlen($answer) ? $answer[$i] : -1;
            // echo $i," ", $ch, "\n";
            // Finish up the previous entry
            if ( ( $ch == -1 || $ch == '=' || $ch == "~" ) && strlen($answer_text) > 0 ) {
                if ( $correct === null || $answer_text === false ) {
                    $errors[] = "Mal-formed answer sequence: ".$raw;
                    $parsed_answer = [];
                    break;
                }
                $parsed_answer[] = array($correct, trim($answer_text), trim($feedback));
                // Set up for the next one
                $correct = null;
                $answer_text = false;
                $feedback = false;
                $in_feedback = false;
            }

            // We are done...
            if ( $ch == -1 ) break;

            // right or wrong?
            if ( $ch == '=' ) {
                $correct = true;
                continue;
            }
            if ( $ch == '~' ) {
                $correct = false;
                continue;
            }

            // right or wrong?
            if ( $ch == '#' && $in_feedback === false ) {
                $in_feedback = true;
                continue;
            }

            if ( $in_feedback ) {
                $feedback .= $ch;
            } else {
                $answer_text .= $ch;
            }

        }
        if ( count($parsed_answer) < 1 ) {
            $errors[] = "Mal-formed answer sequence: ".$raw;
        }
    }
    echo "\nN: ",$name,"\n";
    echo "Q: ",$question,"\n";
    echo "A: ",$answer,"\n";
    echo "Type:",$type,"\n";
    $questions[] = array($name, $question, $answer, $type, $parsed_answer);
}


if ( count($errors) == 0 ) {
    print "Conversion success\n";
} else {
    print "Conversion errors:\n";
    print_r($errors);
}

// print_r($questions);
$XML = simplexml_load_file('xml/assessment.xml');
var_dump($XML);

