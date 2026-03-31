<?php

namespace local_rainmake_backend;

use moodle_url;

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->libdir . '/gradelib.php');
require_login();

class Practice
{
    private const GRADE_ITEM_MODULE = 'rainmakepractice';
    private const GRADE_ITEM_IDNUMBER_PREFIX = 'local_rainmake_backend:practice:';

    private \moodle_database $DB;

    public function __construct()
    {
        global $DB;
        $this->DB = $DB;
    }

    public function getPractice($courseid, $id): ?\stdClass
    {
        $this->migrateLegacyGradeItemsForCourse((int)$courseid);
        $practice = $this->DB->get_record('local_rainmake_backend_practices', ['id' => $id, 'courseid' => $courseid]);

        if ($practice) {
            $practice->questions = array_values($this->DB->get_records(
                'local_rainmake_backend_practice_questions',
                ['practice_id' => $practice->id],
                'sortorder ASC, id ASC'
            ));

            foreach ($practice->questions as &$question) {
                $question->options = array_values($this->DB->get_records(
                    'local_rainmake_backend_practice_question_options',
                    ['question_id' => $question->id],
                    'sortorder ASC, id ASC'
                ));
            }
            unset($question);
        }

        return $practice;
    }

    public function getProve($courseid): ?\stdClass
    {
        $this->migrateLegacyGradeItemsForCourse((int)$courseid);
        $practice = $this->DB->get_record('local_rainmake_backend_practices', ['courseid' => $courseid]);

        if ($practice) {
            $practice->questions = array_values($this->DB->get_records(
                'local_rainmake_backend_practice_questions',
                ['practice_id' => $practice->id],
                'sortorder ASC, id ASC'
            ));

            foreach ($practice->questions as &$question) {
                $question->options = array_values($this->DB->get_records(
                    'local_rainmake_backend_practice_question_options',
                    ['question_id' => $question->id],
                    'sortorder ASC, id ASC'
                ));
            }
            unset($question);
        }

        return $practice;
    }

    public function getPractices($courseid): array
    {
        $this->migrateLegacyGradeItemsForCourse((int)$courseid);
        $practices = $this->DB->get_records('local_rainmake_backend_practices', ['courseid' => $courseid], 'sortorder ASC, id ASC');

        foreach ($practices as &$practice) {
            $practice->questions = array_values($this->DB->get_records(
                'local_rainmake_backend_practice_questions',
                ['practice_id' => $practice->id],
                'sortorder ASC, id ASC'
            ));

            foreach ($practice->questions as &$question) {
                $question->options = array_values($this->DB->get_records(
                    'local_rainmake_backend_practice_question_options',
                    ['question_id' => $question->id],
                    'sortorder ASC, id ASC'
                ));
            }
            unset($question);
        }
        unset($practice);

        return array_values($practices);
    }

    public function saveAnswer($userid, $questionid, $option = null, $courseid = null, $practiceid = null): bool
    {
        global $USER;

        $record = new \stdClass();
        $record->userid = $userid ?? $USER->id;
        $record->question_id = $questionid;
        $record->answer_option = $option;
        $record->course_id = $courseid;
        $record->practice_id = $practiceid;

        return (bool)$this->DB->insert_record('local_rainmake_backend_practice_answers', $record);
    }

    public function syncGradeItemForPractice(int $practiceid): void
    {
        $practice = $this->DB->get_record('local_rainmake_backend_practices', ['id' => $practiceid], '*', MUST_EXIST);
        $this->migrateLegacyGradeItemsForCourse((int)$practice->courseid);

        $itemdetails = [
            'itemname' => $practice->name,
            'idnumber' => $this->getGradeItemIdnumber((int)$practice->id),
            'gradetype' => GRADE_TYPE_VALUE,
            'grademax' => 100,
            'grademin' => 0,
            'hidden' => 0,
        ];

        grade_update(
            'local_rainmake_backend',
            (int)$practice->courseid,
            'manual',
            '',
            (int)$practice->id,
            0,
            null,
            $itemdetails
        );
    }

    private function getGradeItem(int $courseid, int $practiceid): ?\stdClass
    {
        return $this->DB->get_record('grade_items', [
            'courseid' => $courseid,
            'itemtype' => 'manual',
            'iteminstance' => $practiceid,
            'idnumber' => $this->getGradeItemIdnumber($practiceid),
        ], 'id, courseid, itemname, grademax', IGNORE_MISSING);
    }

    private function getGradeItemIdnumber(int $practiceid): string
    {
        return self::GRADE_ITEM_IDNUMBER_PREFIX . $practiceid;
    }

    private function migrateLegacyGradeItem(int $courseid, int $practiceid): void
    {
        $params = [
            'courseid' => $courseid,
            'practiceid' => $practiceid,
            'idnumber' => $this->getGradeItemIdnumber($practiceid),
            'legacyitemmodule' => self::GRADE_ITEM_MODULE,
        ];

        $sql = "SELECT id, itemtype, itemmodule, iteminstance, idnumber
                  FROM {grade_items}
                 WHERE courseid = :courseid
                   AND (
                        idnumber = :idnumber
                        OR (itemmodule = :legacyitemmodule AND iteminstance = :practiceid)
                   )";

        $items = $this->DB->get_records_sql($sql, $params);
        foreach ($items as $item) {
            $updaterecord = (object)[
                'id' => (int)$item->id,
                'itemtype' => 'manual',
                'itemmodule' => '',
                'iteminstance' => $practiceid,
                'itemnumber' => 0,
                'idnumber' => $this->getGradeItemIdnumber($practiceid),
            ];
            $this->DB->update_record('grade_items', $updaterecord);
        }
    }

    public function migrateLegacyGradeItemsForCourse(int $courseid): void
    {
        $practiceids = $this->DB->get_fieldset_select(
            'local_rainmake_backend_practices',
            'id',
            'courseid = :courseid',
            ['courseid' => $courseid]
        );

        foreach ($practiceids as $practiceid) {
            $this->migrateLegacyGradeItem($courseid, (int)$practiceid);
        }
    }

    private function upsertGradeGrade(int $itemid, int $userid, float $finalgrade, string $feedback): void
    {
        $existing = $this->DB->get_record('grade_grades', [
            'itemid' => $itemid,
            'userid' => $userid,
        ], 'id', IGNORE_MISSING);

        $record = (object)[
            'itemid' => $itemid,
            'userid' => $userid,
            'rawgrade' => $finalgrade,
            'finalgrade' => $finalgrade,
            'feedback' => $feedback,
            'feedbackformat' => 0,
            'timemodified' => time(),
        ];

        if ($existing) {
            $record->id = (int)$existing->id;
            $this->DB->update_record('grade_grades', $record);
            return;
        }

        $record->timecreated = time();
        $this->DB->insert_record('grade_grades', $record);
    }

    public function saveSubmissionAndGrade(int $userid, int $courseid, int $practiceid, array $answers): array
    {
        $this->migrateLegacyGradeItemsForCourse($courseid);
        $practice = $this->getPractice($courseid, $practiceid);
        if (!$practice) {
            throw new \moodle_exception('invaliddata', 'error');
        }

        $this->syncGradeItemForPractice($practiceid);

        $this->DB->delete_records('local_rainmake_backend_practice_answers', [
            'userid' => $userid,
            'course_id' => $courseid,
            'practice_id' => $practiceid,
        ]);

        $totalquestions = count($practice->questions);
        $correctanswers = 0;

        foreach ($practice->questions as $question) {
            $selectedoptionid = isset($answers[$question->id]) ? (int)$answers[$question->id] : 0;
            $correctoptionid = 0;

            foreach ($question->options as $option) {
                if (!empty($option->is_correct)) {
                    $correctoptionid = (int)$option->id;
                    break;
                }
            }

            if ($selectedoptionid > 0) {
                $this->saveAnswer($userid, (int)$question->id, (string)$selectedoptionid, $courseid, $practiceid);
            }

            if ($selectedoptionid > 0 && $correctoptionid > 0 && $selectedoptionid === $correctoptionid) {
                $correctanswers++;
            }
        }

        $rawgrade = $totalquestions > 0 ? round(($correctanswers / $totalquestions) * 100, 2) : 0.0;
        $feedback = "Correct answers: {$correctanswers}/{$totalquestions}";

        $grades = [
            [
                'userid' => $userid,
                'rawgrade' => $rawgrade,
                'feedback' => $feedback,
            ],
        ];

        $itemdetails = [
            'itemname' => $practice->name,
            'gradetype' => GRADE_TYPE_VALUE,
            'grademax' => 100,
            'grademin' => 0,
            'hidden' => 0,
        ];

        grade_update(
            'local_rainmake_backend',
            $courseid,
            'manual',
            '',
            $practiceid,
            0,
            $grades,
            $itemdetails
        );

        $gradeitem = $this->getGradeItem($courseid, $practiceid);
        if ($gradeitem) {
            $this->upsertGradeGrade((int)$gradeitem->id, $userid, $rawgrade, $feedback);
        }

        return [
            'practice' => $practice,
            'correctanswers' => $correctanswers,
            'totalquestions' => $totalquestions,
            'rawgrade' => $rawgrade,
            'gradeurl' => new moodle_url('/grade/report/user/index.php', ['id' => $courseid]),
            'careerpathurl' => new moodle_url('/theme/rainmake/careerpath.php', [
                'id' => $courseid,
                'tab' => ($practice->name === 'prove') ? 'prove' : 'practice',
            ]),
        ];
    }

    public function getUserPracticeResult(int $courseid, int $practiceid, int $userid): array
    {
        $this->migrateLegacyGradeItemsForCourse($courseid);

        $practice = $this->DB->get_record('local_rainmake_backend_practices', [
            'id' => $practiceid,
            'courseid' => $courseid,
        ], '*', IGNORE_MISSING);

        if (!$practice) {
            return [
                'hasgrade' => false,
                'grade' => null,
                'scoredisplay' => '0/100',
                'progressdisplay' => '0%',
                'feedback' => '',
                'actionlabel' => 'Start',
            ];
        }

        $item = $this->getGradeItem($courseid, $practiceid);

        if (!$item) {
            return [
                'hasgrade' => false,
                'grade' => null,
                'scoredisplay' => '0/100',
                'progressdisplay' => '0%',
                'feedback' => '',
                'actionlabel' => 'Start',
            ];
        }

        $grade = $this->DB->get_record('grade_grades', [
            'itemid' => $item->id,
            'userid' => $userid,
        ], 'finalgrade, feedback', IGNORE_MISSING);

        $grademax = isset($item->grademax) ? (float)$item->grademax : 100.0;
        $finalgrade = ($grade && $grade->finalgrade !== null) ? round((float)$grade->finalgrade, 2) : null;
        $scorevalue = $finalgrade !== null ? $this->formatGradeValue($finalgrade) : '0';
        $maxvalue = $this->formatGradeValue($grademax);
        $progressvalue = $finalgrade !== null && $grademax > 0
            ? round(($finalgrade / $grademax) * 100)
            : 0;

        return [
            'hasgrade' => $finalgrade !== null,
            'grade' => $finalgrade,
            'scoredisplay' => $scorevalue . '/' . $maxvalue,
            'progressdisplay' => $progressvalue . '%',
            'feedback' => $grade->feedback ?? '',
            'actionlabel' => $finalgrade !== null ? 'Retake' : 'Start',
        ];
    }

    private function formatGradeValue(float $value): string
    {
        if (floor($value) == $value) {
            return (string)(int)$value;
        }

        return number_format($value, 2, '.', '');
    }
}
