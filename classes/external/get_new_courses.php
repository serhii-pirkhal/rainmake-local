<?php

namespace local_rainmake_backend\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

class get_new_courses extends external_api
{

    public static function execute_parameters(){
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
        ]);
    }

    public static function execute($courseid) {
        global $DB, $CFG;
        require_once($CFG->dirroot . '/course/lib.php');
        $oldIds = $DB->get_records('local_rainmake_backend_careerpath_courses', array('careerpath_id' => $courseid));
        $oldIds = array_filter($oldIds, function($course){
            return $course->course_id;
        });
        $sql = "SELECT c.id, c.fullname
                  FROM {course} c
                  JOIN {local_rainmake_backend_course_types} t ON t.course_id = c.id
                 WHERE " . $DB->sql_compare_text('t.type') . " = :type";
        $courses = $DB->get_records_sql($sql, ['type' => 'course']);
        $courses = array_filter($courses, function($course) use ($oldIds){
            foreach ($oldIds as $key => $oldId) {
                if ($oldId == $course->id) {
                    unset($oldIds[$key]);
                    return null;
                }
            }
            return $course;
        });
        foreach ($courses as &$course) {
            $context = \context_course::instance($course->id);
            $fs = get_file_storage();
            $files = $fs->get_area_files($context->id, 'local_rainmake_backend', 'courseimage', $course->id, 'timemodified DESC', false);

            // Moodle sometimes stores a "directory placeholder" file with filename='.' in area files.
            // Skip it so we don't generate a broken thumbnail URL.
            $file = null;
            foreach ($files as $candidate) {
                if (!$candidate) continue;
                if ($candidate->get_filename() === '.') continue;
                $file = $candidate;
                break;
            }

            if ($file) {
                $course->img = \moodle_url::make_pluginfile_url(
                    $file->get_contextid(),
                    $file->get_component(),
                    $file->get_filearea(),
                    $file->get_itemid(),
                    $file->get_filepath(),
                    $file->get_filename()
                )->out();
            }
        }
        unset($course);

        return [
            'success' => true,
            'courses' => array_values($courses),
        ];
    }

    public static function execute_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'True if operation succeeded'),
            'courses' => new external_multiple_structure(
                new external_single_structure([
                    'id' => new external_value(PARAM_INT, 'Course ID'),
                    'fullname' => new external_value(PARAM_TEXT, 'Fullname'),
                    'img' => new external_value(PARAM_TEXT, 'Image', VALUE_OPTIONAL),
                ])
                , 'courses'),
        ]);
    }
}
