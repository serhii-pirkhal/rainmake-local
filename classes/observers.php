<?php
namespace local_rainmake_backend;

class observers {
    public static function on_course_created(\core\event\course_created $event) {
        $course = $event->get_record_snapshot('course', $event->objectid);
        debugging("Course created: {$course->fullname}", DEBUG_DEVELOPER);
    }

    public static function on_user_created(\core\event\user_created $event): void {
        global $DB, $SESSION;

        $linkedin = '';
        if (!empty($GLOBALS['local_rainmake_backend_signup_social']['linkedin'])) {
            $linkedin = trim((string)$GLOBALS['local_rainmake_backend_signup_social']['linkedin']);
            debugging('Rainmake signup: observer read linkedin from GLOBALS = ' . $linkedin, DEBUG_DEVELOPER);
        } else if (!empty($SESSION->local_rainmake_backend_signup_social['linkedin'])) {
            $linkedin = trim((string)$SESSION->local_rainmake_backend_signup_social['linkedin']);
            debugging('Rainmake signup: observer read linkedin from SESSION = ' . $linkedin, DEBUG_DEVELOPER);
        } else if (!empty($_POST['linkedinprofile'])) {
            $linkedin = trim((string)$_POST['linkedinprofile']);
            debugging('Rainmake signup: observer read linkedin from POST[linkedinprofile] = ' . $linkedin, DEBUG_DEVELOPER);
        } else if (!empty($_POST['linkedin'])) {
            $linkedin = trim((string)$_POST['linkedin']);
            debugging('Rainmake signup: observer read linkedin from POST[linkedin] = ' . $linkedin, DEBUG_DEVELOPER);
        } else {
            debugging('Rainmake signup: observer triggered but no linkedin value found', DEBUG_DEVELOPER);
            return;
        }

        unset($SESSION->local_rainmake_backend_signup_social);
        unset($GLOBALS['local_rainmake_backend_signup_social']);

        if ($linkedin !== '' && !preg_match('#^https?://#i', $linkedin)) {
            $linkedin = 'https://' . $linkedin;
        }

        $linkedin = clean_param($linkedin, PARAM_URL);

        if ($linkedin === '') {
            debugging('Rainmake signup: observer sanitized linkedin to empty string', DEBUG_DEVELOPER);
            return;
        }

        $userid = (int)$event->objectid;
        $social = $DB->get_record('local_rainmake_backend_user_social', ['userid' => $userid]);

        if ($social) {
            $social->linkedin = $linkedin;
            $DB->update_record('local_rainmake_backend_user_social', $social);
            debugging('Rainmake signup: observer updated social row for userid ' . $userid, DEBUG_DEVELOPER);
            return;
        }

        $DB->insert_record('local_rainmake_backend_user_social', [
            'userid' => $userid,
            'linkedin' => $linkedin,
        ]);
        debugging('Rainmake signup: observer inserted social row for userid ' . $userid, DEBUG_DEVELOPER);
    }
}
