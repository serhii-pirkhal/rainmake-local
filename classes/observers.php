<?php
namespace local_rainmake_backend;

class observers {
    public static function on_course_created(\core\event\course_created $event) {
        $course = $event->get_record_snapshot('course', $event->objectid);
        debugging("Course created: {$course->fullname}", DEBUG_DEVELOPER);
    }

    public static function on_user_created(\core\event\user_created $event): void {
        global $DB, $SESSION;

        if (empty($SESSION->local_rainmake_backend_signup_social['linkedin'])) {
            return;
        }

        $linkedin = clean_param($SESSION->local_rainmake_backend_signup_social['linkedin'], PARAM_URL);
        unset($SESSION->local_rainmake_backend_signup_social);

        if ($linkedin === '') {
            return;
        }

        $userid = (int)$event->objectid;
        $social = $DB->get_record('local_rainmake_backend_user_social', ['userid' => $userid]);

        if ($social) {
            $social->linkedin = $linkedin;
            $DB->update_record('local_rainmake_backend_user_social', $social);
            return;
        }

        $DB->insert_record('local_rainmake_backend_user_social', [
            'userid' => $userid,
            'linkedin' => $linkedin,
        ]);
    }
}
