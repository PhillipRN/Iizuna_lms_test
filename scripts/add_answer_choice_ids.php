<?php

declare(strict_types=1);

/**
 * Backfill script to add answer_id to multiple-choice answers and results.
 *
 * Usage:
 *   php scripts/add_answer_choice_ids.php            # dry-run (no DB updates)
 *   php scripts/add_answer_choice_ids.php --apply    # execute updates
 *   php scripts/add_answer_choice_ids.php --quiz=123 # limit to quiz_id 123 (dry-run unless --apply)
 */

require __DIR__ . '/../app/bootstrap.php';

use IizunaLMS\Helpers\PDOHelper;

$options = getopt('', ['apply', 'quiz:']);
$apply = array_key_exists('apply', $options);
$targetQuizId = isset($options['quiz']) ? (int)$options['quiz'] : null;

$pdo = PDOHelper::GetPDO();
$pdo->setAttribute(PDO::ATTR_EMULATE_PREPARES, false);

$questionTypes = [
    'multiple_choice_question',
    'vertical_multiple_choice_question',
];

echo "=== Add answer_id to multiple-choice answers ===\n";
echo $apply ? "[EXECUTION MODE]\n" : "[DRY-RUN]\n";
if ($targetQuizId !== null) {
    echo "Target quiz_id: {$targetQuizId}\n";
}

$selectSql = 'SELECT id, json FROM json_quiz';
if ($targetQuizId !== null) {
    $selectSql .= ' WHERE id = :quiz_id';
}
$selectStmt = $pdo->prepare($selectSql);
if ($targetQuizId !== null) {
    $selectStmt->bindValue(':quiz_id', $targetQuizId, PDO::PARAM_INT);
}
$selectStmt->execute();

$updateQuizStmt = $pdo->prepare('UPDATE json_quiz SET json = :json WHERE id = :id');
$selectResultStmt = $pdo->prepare('SELECT id, answers_json FROM json_quiz_result WHERE json_quiz_id = :quiz_id');
$updateResultStmt = $pdo->prepare('UPDATE json_quiz_result SET answers_json = :json WHERE id = :id');

$quizCount = 0;
$quizUpdated = 0;
$resultsUpdated = 0;
$ambiguousCount = 0;

while ($row = $selectStmt->fetch(PDO::FETCH_ASSOC)) {
    ++$quizCount;
    $quizId = (int)$row['id'];
    $json = json_decode($row['json'], true);
    if (!is_array($json) || empty($json['questions'])) {
        echo "[WARN] quiz_id {$quizId}: invalid JSON, skipped.\n";
        continue;
    }

    $answerMap = []; // [questionId][answerText][] = answer_id
    $quizModified = false;

    foreach ($json['questions'] as &$question) {
        $questionId = isset($question['question_id']) ? (string)$question['question_id'] : null;
        if ($questionId === null) {
            continue;
        }
        if (!in_array($question['question_type'] ?? '', $questionTypes, true)) {
            continue;
        }
        if (empty($question['answers']) || !is_array($question['answers'])) {
            continue;
        }

        foreach ($question['answers'] as $idx => &$answer) {
            $answerId = $answer['answer_id'] ?? null;
            if (empty($answerId)) {
                $answerId = sprintf('q%s_a%s', $questionId, $idx);
                $answer['answer_id'] = $answerId;
                $quizModified = true;
            }
            $answerText = $answer['answer_text'] ?? '';
            $answerMap[$questionId][$answerText][] = $answerId;
        }
        unset($answer);
    }
    unset($question);

    if ($quizModified) {
        ++$quizUpdated;
        if ($apply) {
            $updateQuizStmt->execute([
                ':json' => json_encode($json, JSON_UNESCAPED_UNICODE),
                ':id' => $quizId,
            ]);
        }
        echo "[INFO] quiz_id {$quizId}: answer_id added to JSON.\n";
    }

    if (empty($answerMap)) {
        continue;
    }

    $selectResultStmt->execute([':quiz_id' => $quizId]);
    while ($resultRow = $selectResultStmt->fetch(PDO::FETCH_ASSOC)) {
        $resultId = (int)$resultRow['id'];
        $answersJson = json_decode($resultRow['answers_json'], true);
        if (!is_array($answersJson)) {
            continue;
        }

        $resultModified = false;
        foreach ($answersJson as $questionId => &$answerData) {
            if (isset($answerData['answer_id']) && $answerData['answer_id'] !== '') {
                continue;
            }
            $questionId = (string)$questionId;
            if (!isset($answerMap[$questionId])) {
                continue;
            }
            $answerText = $answerData['answer'] ?? '';
            $candidates = $answerMap[$questionId][$answerText] ?? null;
            if (empty($candidates)) {
                continue;
            }
            if (count($candidates) > 1) {
                ++$ambiguousCount;
                continue;
            }
            $answerData['answer_id'] = $candidates[0];
            $resultModified = true;
        }
        unset($answerData);

        if ($resultModified) {
            ++$resultsUpdated;
            if ($apply) {
                $updateResultStmt->execute([
                    ':json' => json_encode($answersJson, JSON_UNESCAPED_UNICODE),
                    ':id' => $resultId,
                ]);
            }
        }
    }
}

echo "=== Summary ===\n";
echo "Processed quizzes : {$quizCount}\n";
echo "Quiz JSON updated : {$quizUpdated}\n";
echo "Result rows updated: {$resultsUpdated}\n";
echo "Ambiguous matches  : {$ambiguousCount}\n";
echo $apply ? "Changes applied.\n" : "Dry-run only (no DB updates).\n";
