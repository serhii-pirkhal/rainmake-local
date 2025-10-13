<?php

namespace local_rainmake_backend;
use core\check\performance\debugging;
use moodle_url;
use context_course;

require_once(__DIR__ . '/../../../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_login();

class Practice
{
    private \moodle_database $DB;

    public function __construct()
    {
        global $DB;
        $this->DB = $DB;
    }

    public function getPractice($courseid): ?\stdClass
    {
        $practice = $this->DB->get_record('local_rainmake_backend_practices', ['courseid' => $courseid]);

        if ($practice) {
            $practice->questions = array_values($this->DB->get_records('local_rainmake_backend_practice_questions', ['practice_id' => $practice->id]));

            foreach ($practice->questions as &$question) {
                $question->options = array_values($this->DB->get_records('local_rainmake_backend_practice_question_options', ['question_id' => $question->id]));
            }
        }

        return $practice;
    }
    public function getPractices($courseid): array
    {
        $practices = $this->DB->get_records('local_rainmake_backend_practices', ['courseid' => $courseid]);

        foreach ($practices as &$practice) {
            $practice->questions = array_values($this->DB->get_records('local_rainmake_backend_practice_questions', ['practice_id' => $practice->id]));
            foreach ($practice->questions as &$question) {
                $question->options = array_values($this->DB->get_records('local_rainmake_backend_practice_question_options', ['question_id' => $question->id]));
            }
            unset($question);
        }
        return array_values($practices);
    }

    public function saveAnswer($userid, $questionid, $option = null, $courseid = null, $practiceid = null): bool {
        global $USER, $DB;

        $record = new \stdClass();
        $record->userid = $userid ?? $USER->id;
        $record->question_id = $questionid;
        $record->option = $option;
        $record->course_id = $courseid;
        $record->practice_id = $practiceid;

        return $DB->insert_record('local_rainmake_backend_practice_answers', $record);
    }
}