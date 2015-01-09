<?php

$errors = array();
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
        
        $errors[] = "Short answer questions not yet supported: ".$raw;
        continue;
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
    $answers = array();
    $parsed_answer = false;
    $correct_answers = 0;
    if ( $type == 'short_answer_question' || $type == 'multiple_choice_question') {
        $parsed_answer = array();
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
                    $parsed_answer = array();
                    break;
                }
                if ( $correct ) $correct_answers++;
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
        if ( $correct_answers < 1 ) {
            $errors[] = "No correct answers found: ".$raw;
            continue;
        }
        if ( $correct_answers > 1 ) {
            $type = 'multiple_answers_question';
            $errors[] = "No support for multiple_answers_question type: ".$raw;
            continue;
        }
    }
    // echo "\nN: ",$name,"\nQ: ",$question,"\nA: ",$answer,"\nType:",$type,"\n";
    $qobj = new stdClass();
    $qobj->name = $name;
    $qobj->question = $question;
    $qobj->answer = $answer;
    $qobj->type = $type;
    $qobj->parsed_answer = $parsed_answer;
    $qobj->correct_answers = $correct_answers;
    $questions[] = $qobj;
}

