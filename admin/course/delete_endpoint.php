<?php

require_once(__DIR__ . '/../../../../config.php');
require_once($CFG->dirroot . '/course/lib.php');
require_once($CFG->libdir . '/ddllib.php');

require_login();
require_sesskey();

$courseid = required_param('id', PARAM_INT);
$returnurl = new moodle_url('/theme/rainmake/admincourses.php');

$systemcontext = context_system::instance();
require_capability('moodle/course:create', $systemcontext);

$course = $DB->get_record('course', ['id' => $courseid], '*', MUST_EXIST);
$dbmanager = $DB->get_manager();
$typesql = "SELECT *
              FROM {local_rainmake_backend_course_types}
             WHERE course_id = :courseid
               AND " . $DB->sql_compare_text('type') . " = :typevalue";
$coursetype = $DB->get_record_sql($typesql, [
    'courseid' => $courseid,
    'typevalue' => 'course',
], IGNORE_MISSING);

if (!$coursetype) {
    throw new moodle_exception('invalidcourse');
}

$practiceids = $DB->get_fieldset_select('local_rainmake_backend_practices', 'id', 'courseid = :courseid', [
    'courseid' => $courseid,
]);
if ($practiceids) {
    [$practiceinsql, $practiceparams] = $DB->get_in_or_equal($practiceids, SQL_PARAMS_NAMED);
    $questionids = $DB->get_fieldset_select(
        'local_rainmake_backend_practice_questions',
        'id',
        "practice_id $practiceinsql",
        $practiceparams
    );

    if ($questionids) {
        [$questioninsql, $questionparams] = $DB->get_in_or_equal($questionids, SQL_PARAMS_NAMED);
        $DB->delete_records_select('local_rainmake_backend_practice_question_options', "question_id $questioninsql", $questionparams);
        $DB->delete_records_select('local_rainmake_backend_practice_answers', "question_id $questioninsql", $questionparams);
        $DB->delete_records_select('local_rainmake_backend_practice_questions', "id $questioninsql", $questionparams);
    }

    $DB->delete_records_select('local_rainmake_backend_practice_answers', "practice_id $practiceinsql", $practiceparams);
    $DB->delete_records_select('local_rainmake_backend_practices', "id $practiceinsql", $practiceparams);
}

$sessionids = $DB->get_fieldset_select('local_rainmake_backend_sessions', 'id', 'courseid = :courseid', [
    'courseid' => $courseid,
]);
if ($sessionids) {
    [$sessioninsql, $sessionparams] = $DB->get_in_or_equal($sessionids, SQL_PARAMS_NAMED);
    $lectureids = $DB->get_fieldset_select(
        'local_rainmake_backend_lectures',
        'id',
        "sessionid $sessioninsql",
        $sessionparams
    );

    if ($lectureids) {
        [$lectureinsql, $lectureparams] = $DB->get_in_or_equal($lectureids, SQL_PARAMS_NAMED);
        $DB->delete_records_select('local_rainmake_backend_lecture_views', "lectureid $lectureinsql", $lectureparams);
        $DB->delete_records_select('local_rainmake_backend_lecture_video', "lectureid $lectureinsql", $lectureparams);
        $DB->delete_records_select('local_rainmake_backend_lecture_files', "lectureid $lectureinsql", $lectureparams);
        $DB->delete_records_select('local_rainmake_backend_lectures', "id $lectureinsql", $lectureparams);
    }

    $DB->delete_records_select('local_rainmake_backend_sessions', "id $sessioninsql", $sessionparams);
}

$taskids = $DB->get_fieldset_select('assignment_tasks', 'id', 'course_id = :courseid', [
    'courseid' => $courseid,
]);
if ($taskids && $dbmanager->table_exists(new xmldb_table('assignment_tasks_students'))
        && $dbmanager->table_exists(new xmldb_table('assignment_tasks_files'))
        && $dbmanager->table_exists(new xmldb_table('assignment_tasks_courses'))
        && $dbmanager->table_exists(new xmldb_table('assignment_tasks_curriculum'))
        && $dbmanager->table_exists(new xmldb_table('assignment_tasks'))) {
    [$taskinsql, $taskparams] = $DB->get_in_or_equal($taskids, SQL_PARAMS_NAMED);
    $DB->delete_records_select('assignment_tasks_students', "task_id $taskinsql", $taskparams);
    $DB->delete_records_select('assignment_tasks_files', "task_id $taskinsql", $taskparams);
    $DB->delete_records_select('assignment_tasks_courses', "task_id $taskinsql", $taskparams);
    $DB->delete_records_select('assignment_tasks_curriculum', "task_id $taskinsql", $taskparams);
    $DB->delete_records_select('assignment_tasks', "id $taskinsql", $taskparams);
}

if ($dbmanager->table_exists(new xmldb_table('assignment_tasks_courses'))) {
    $DB->delete_records('assignment_tasks_courses', ['course_id' => $courseid]);
}
if ($dbmanager->table_exists(new xmldb_table('assignment_tasks_curriculum'))) {
    $DB->delete_records('assignment_tasks_curriculum', ['course_id' => $courseid]);
}
$DB->delete_records('local_rainmake_backend_lecture_views', ['courseid' => $courseid]);
$DB->delete_records('local_rainmake_backend_coursemeta', ['courseid' => $courseid]);
$DB->delete_records('local_rainmake_backend_course_types', ['course_id' => $courseid]);
$DB->delete_records('local_rainmake_backend_careerpath_courses', ['course_id' => $courseid]);

delete_course($course, false);

redirect($returnurl);
