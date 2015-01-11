<?php

$errors = array();
$raw_questions = array();
$question = "";
$lines = explode("\n", $text);
foreach ( $lines as $line ) {
    $line = rtrim($line);
    // print $line."\n";
    if ( strpos($line, "//") === 0 ) continue;
    if ($line == "" ) {
        if ( strlen($question) > 0 ) {
            $raw_questions[] = $question;
            $question = "";
        }
        continue;
    }
    if ( strlen($question) > 0 ) $question .= "\n";
    $question .= $line;
}

if ( strlen($question) > 0 ) {
    $raw_questions[] = $question;
}

// var_dump_pre($raw_questions);

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
    // We won't know until later if the question is short answer or not.
    if ( $epos == strlen($text)-1 ) {
        $question = trim(substr($text,0,$spos-1));
        $sa_question = trim(substr($text,0,$spos-1)) . "[_____]";
    } else {
        $question = trim(substr($text,0,$spos-1)) . " " . trim(substr($text,$epos+1));
        $sa_question = trim(substr($text,0,$spos-1)) . " [_____] " . trim(substr($text,$epos+1));
    }

    if ( strpos($answer, "->" ) > 0 ) {
        $type = 'matching_question'; // CHECK THIS
        $errors[] = "Matching questions not yet supported: ".$raw;
        continue;
    } else if ( strpos($answer,"T") === 0 || strpos($answer, "F") === 0 ) {
        $type = 'true_false_question';
    } else if ( strlen($answer) < 1 ) {
        $type = 'essay_question';
    } else if ( strpos($answer, '#') === 0 ) {
        $type = 'numerical_question';
        $errors[] = "Numerical questions not yet supported: ".$raw;
        continue;
    }  else if ( strpos($answer,"=") === 0 || strpos($answer, "~") === 0 ) {
        $type = 'multiple_choice_question';  // Also will be multiple_answer and short_answer
    } else { 
        $errors[] = "Could not determine question type: ".$raw;
        continue;
    }
    $answers = array();
    $parsed_answer = false;
    $correct_answers = 0;
    $incorrect_answers = 0;
    // Also will be multiple_answer_question and short_answer_question
    if ( $type == 'multiple_choice_question') {
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
                if ( $correct ) {
                    $correct_answers++;
                } else {
                    $incorrect_answers++;
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
            continue;
        }
        if ( $correct_answers < 1 ) {
            $errors[] = "No correct answers found: ".$raw;
            continue;
        } else if ( $correct_answers == 1 && $incorrect_answers > 0 ) {
            $type = 'multiple_choice_question';
        } else if ( $correct_answers > 1 && $incorrect_answers > 0 ) {
            $type = 'multiple_answers_question';
        } else if ( $correct_answers > 0 && $incorrect_answers == 0 ) {
            $type = 'short_answer_question';
            $question = $sa_question;
        } else {
            $errors[] = "Could not determine question type: ".$raw;
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

// var_dump_pre($questions);

