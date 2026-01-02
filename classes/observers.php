<?php
namespace local_rainmake_backend;

class observers {
    public static function on_course_created(\core\event\course_created $event) {
        $course = $event->get_record_snapshot('course', $event->objectid);
        debugging("Course created: {$course->fullname}", DEBUG_DEVELOPER);
    }
}
