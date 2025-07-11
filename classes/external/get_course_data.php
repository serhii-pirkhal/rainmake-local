<?php
namespace local_rainmake_backend\external;

use external_function_parameters;
use external_value;
use external_single_structure;
use external_api;
use context_course;
use core_course\external\course_summary_exporter;

class get_course_data extends external_api {

    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
        ]);
    }

    public static function execute($courseid) {
        global $DB;
        $params = self::validate_parameters(self::execute_parameters(), ['courseid' => $courseid]);

        $course = $DB->get_record('course', ['id' => $params['courseid']], '*', MUST_EXIST);
        $context = context_course::instance($course->id);

        self::validate_context($context);

        return [
            'fullname'  => $course->fullname,
            'shortname' => $course->shortname,
            'idnumber'  => $course->idnumber ?? '',
        ];
    }

    public static function execute_returns() {
        return new external_single_structure([
            'fullname'  => new external_value(PARAM_TEXT, 'Course full name'),
            'shortname' => new external_value(PARAM_TEXT, 'Course short name'),
            'idnumber'  => new external_value(PARAM_TEXT, 'ID number'),
        ]);
    }
}
