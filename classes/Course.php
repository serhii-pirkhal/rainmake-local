<?php

namespace local_rainmake_backend;
use context_course;

require_once(__DIR__ . '/../../../config.php');
require_login();

class Course {
    private \moodle_database $DB;

    public function __construct()
    {
        global $DB;
        $this->DB = $DB;
    }


    public function getMyCoursesCount(): int
    {
        return $this->DB->count_records('course');
    }
    public function getMyCourses($page=1, $perpage=10, $filters = null, $sort = null): array
    {
        $offset = ($page - 1) * $perpage;
        $courses = $this->DB->get_records('course', null, '', 'id, category, fullname, shortname', $offset, $perpage);

        foreach ($courses as &$course) {
            $course->category = $this->DB->get_record('course_categories', array('id' => $course->category), 'id, name, description, coursecount');
            $context = context_course::instance($course->id);
            $course->usercount = count_enrolled_users($context);
            $course->img = \core_course\external\course_summary_exporter::get_course_image($course);
            $course->modulescount = $this->DB->count_records('course_modules', array('course' => $course->id));
        }
        unset($course);


        return $courses;
    }
}
