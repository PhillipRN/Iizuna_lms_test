<?php
require_once (__DIR__ . '/../bootstrap.php');

use IizunaLMS\Commands\CorrectAnswerRate;
use IizunaLMS\Commands\OnigiriJsonQuizCorrectAnswerRate;

(new CorrectAnswerRate())->SummaryAndRegist();
(new OnigiriJsonQuizCorrectAnswerRate())->SummaryAndRegist();