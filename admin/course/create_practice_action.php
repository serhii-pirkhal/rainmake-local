<?php
require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once(__DIR__ . '/../../classes/Practice.php');

function createPracticeAction(?array $practices, string $courseid): void
{
    global $DB;

    $practiceids = [];
    $tx = $DB->start_delegated_transaction();

    try {
        foreach ($practices as $key => $practice) {
            $practiceR = new stdClass();
            $practiceR->timecreated = time();
            $practiceR->courseid = $courseid;
            $practiceR->name = clean_param($practice['name'] ?? '', PARAM_TEXT);
            $practiceR->type = clean_param($practice['type'] ?? '', PARAM_TEXT);

            if (is_number($key)) {
                $practiceR->id = $key;
                $practiceR->timemodified = time();
                $DB->update_record('local_rainmake_backend_practices', $practiceR);
                $practiceids[] = (int)$key;

                foreach (($practice['questions'] ?? []) as $qKey => $question) {
                    if ((bool)($question['delete'] ?? false)) {
                        $DB->delete_records('local_rainmake_backend_practice_questions', ['id' => $qKey]);
                        continue;
                    }

                    $correctoptionkey = isset($question['correct_option']) ? (string)$question['correct_option'] : null;

                    $questionR = new stdClass();
                    $questionR->practice_id = $key;
                    $questionR->question = clean_param($question['question'] ?? '', PARAM_TEXT);
                    $questionR->options = json_encode(array_values($question['options'] ?? []));

                    if (is_number($qKey)) {
                        $questionR->timeupdated = time();
                        $questionR->id = $qKey;
                        $DB->update_record('local_rainmake_backend_practice_questions', $questionR);

                        foreach (($question['options'] ?? []) as $oKey => $option) {
                            if ((bool)($option['delete'] ?? false)) {
                                if (is_number($oKey)) {
                                    $DB->delete_records('local_rainmake_backend_practice_question_options', ['id' => $oKey]);
                                }
                                continue;
                            }

                            $optionR = new stdClass();
                            $optionR->question_id = $qKey;
                            $optionR->content = clean_param($option['content'] ?? '', PARAM_TEXT);
                            $optionR->is_correct = ($correctoptionkey !== null && (string)$oKey === $correctoptionkey) ? 1 : 0;
                            $optionR->sortorder = is_number($oKey) ? 0 : 0;

                            if (is_number($oKey)) {
                                $optionR->id = $oKey;
                                $optionR->timemodified = time();
                                $DB->update_record('local_rainmake_backend_practice_question_options', $optionR);
                            } else {
                                $optionR->timecreated = time();
                                $DB->insert_record('local_rainmake_backend_practice_question_options', $optionR);
                            }
                        }
                    } else {
                        $questionR->timecreated = time();
                        $qId = $DB->insert_record('local_rainmake_backend_practice_questions', $questionR);

                        foreach (($question['options'] ?? []) as $oKey => $option) {
                            if ((bool)($option['delete'] ?? false)) {
                                continue;
                            }

                            $optionR = new stdClass();
                            $optionR->question_id = $qId;
                            $optionR->content = clean_param($option['content'] ?? '', PARAM_TEXT);
                            $optionR->is_correct = ($correctoptionkey !== null && (string)$oKey === $correctoptionkey) ? 1 : 0;
                            $optionR->timecreated = time();
                            $DB->insert_record('local_rainmake_backend_practice_question_options', $optionR);
                        }
                    }
                }
            } else {
                $id = $DB->insert_record('local_rainmake_backend_practices', $practiceR);
                $practiceids[] = (int)$id;

                foreach (($practice['questions'] ?? []) as $qKey => $question) {
                    if ((bool)($question['delete'] ?? false)) {
                        continue;
                    }

                    $correctoptionkey = isset($question['correct_option']) ? (string)$question['correct_option'] : null;

                    $questionR = new stdClass();
                    $questionR->timecreated = time();
                    $questionR->practice_id = $id;
                    $questionR->question = clean_param($question['question'] ?? '', PARAM_TEXT);
                    $qId = $DB->insert_record('local_rainmake_backend_practice_questions', $questionR);

                    foreach (($question['options'] ?? []) as $oKey => $option) {
                        if ((bool)($option['delete'] ?? false)) {
                            continue;
                        }

                        $optionR = new stdClass();
                        $optionR->question_id = $qId;
                        $optionR->content = clean_param($option['content'] ?? '', PARAM_TEXT);
                        $optionR->is_correct = ($correctoptionkey !== null && (string)$oKey === $correctoptionkey) ? 1 : 0;
                        $optionR->timecreated = time();
                        $DB->insert_record('local_rainmake_backend_practice_question_options', $optionR);
                    }
                }
            }
        }

        $tx->allow_commit();
    } catch (Exception $e) {
        $tx->rollback($e);
        throw $e;
    }

    $practicehelper = new \local_rainmake_backend\Practice();
    foreach (array_unique($practiceids) as $practiceid) {
        $practicehelper->syncGradeItemForPractice((int)$practiceid);
    }
}
