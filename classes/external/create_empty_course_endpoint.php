<?php

namespace local_rainmake_backend\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

class create_empty_course_endpoint extends external_api
{

    public static function execute_parameters(){
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
        ]);
    }

    public static function execute($courseid) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/course/lib.php');
        $category = $DB->get_records('course_categories', null, 'id ASC', '*', 0, 1);
        $categoryid = reset($category)->id;
        require_login();
        $catctx = \context_coursecat::instance($categoryid, MUST_EXIST);
        require_capability('moodle/course:create', $catctx);
        $course = (object)[
            'category' => $categoryid,
            'fullname' => "",
            'shortname' => "",
        ];
        $course = create_course($course);
        $careerPathCourse = (object)[
            'careerpath_id' => $courseid,
            'course_id' => $course->id,
            'timecreated' => time(),
        ];
        $DB->insert_record('local_rainmake_backend_careerpath_courses', $careerPathCourse);
        return [
            'success' => true,
            'courseid' => $course->id,
            'message' => 'Course created successfully'
        ];
    }

    public static function execute_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'True if operation succeeded'),
            'courseid' => new external_value(PARAM_INT, 'ID of the course created'),
            'message' => new external_value(PARAM_TEXT, 'Optional status message')
        ]);
    }
}

