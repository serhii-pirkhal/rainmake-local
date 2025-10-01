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
}