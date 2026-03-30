<?php

require_once(__DIR__ . '/../../../../config.php');
require_login();
require_sesskey();

function rainmake_get_or_create_manual_instance_for_course(int $courseid): stdClass {
    global $DB;

    $instances = enrol_get_instances($courseid, true);
    foreach ($instances as $instance) {
        if ($instance->enrol === 'manual') {
            return $instance;
        }
    }

    $plugin = enrol_get_plugin('manual');
    if (!$plugin) {
        throw new moodle_exception('Manual enrolment plugin not enabled on site');
    }

    $instanceid = $DB->insert_record('enrol', (object)[
        'enrol' => 'manual',
        'status' => ENROL_INSTANCE_ENABLED,
        'courseid' => $courseid,
        'roleid' => 5,
        'timecreated' => time(),
        'timemodified' => time(),
    ]);

    return $DB->get_record('enrol', ['id' => $instanceid], '*', MUST_EXIST);
}

function rainmake_get_student_enrolled_userids(int $courseid): array {
    global $DB;

    $sql = "SELECT DISTINCT ue.userid
              FROM {enrol} e
              JOIN {user_enrolments} ue
                ON ue.enrolid = e.id
               AND ue.status = 0
              JOIN {role_assignments} ra
                ON ra.userid = ue.userid
              JOIN {context} ctx
                ON ctx.id = ra.contextid
               AND ctx.contextlevel = :contextlevel
               AND ctx.instanceid = e.courseid
             WHERE e.courseid = :courseid
               AND ra.roleid = :roleid";

    $userids = $DB->get_fieldset_sql($sql, [
        'courseid' => $courseid,
        'roleid' => 5,
        'contextlevel' => CONTEXT_COURSE,
    ]);

    return array_map('intval', $userids);
}

function rainmake_manual_enrol_users(int $courseid, array $userids): void {
    if (empty($userids)) {
        return;
    }

    $enrol = enrol_get_plugin('manual');
    if (!$enrol) {
        throw new moodle_exception('Manual enrolment plugin not enabled on site');
    }

    $instance = rainmake_get_or_create_manual_instance_for_course($courseid);
    $roleid = !empty($instance->roleid) ? (int)$instance->roleid : 5;

    foreach ($userids as $userid) {
        $enrol->enrol_user($instance, (int)$userid, $roleid);
    }
}

function rainmake_unenrol_users(int $courseid, array $userids, array $methods): void {
    if (empty($userids)) {
        return;
    }

    $instances = enrol_get_instances($courseid, true);
    foreach ($instances as $instance) {
        if (!in_array($instance->enrol, $methods, true)) {
            continue;
        }

        $plugin = enrol_get_plugin($instance->enrol);
        if (!$plugin) {
            continue;
        }

        foreach ($userids as $userid) {
            $plugin->unenrol_user($instance, (int)$userid);
        }
    }
}

$courseid = required_param('careerpath_id', PARAM_INT);
$users = $_POST['users'] ?? [];

$selecteduserids = [];
foreach ($users as $userid => $user) {
    if (!empty($user['selected'])) {
        $selecteduserids[] = (int)$userid;
    }
}
$selecteduserids = array_values(array_unique($selecteduserids));

$currentuserids = rainmake_get_student_enrolled_userids($courseid);

$adduserids = array_values(array_diff($selecteduserids, $currentuserids));
$removeuserids = array_values(array_diff($currentuserids, $selecteduserids));

rainmake_manual_enrol_users($courseid, $adduserids);
rainmake_unenrol_users($courseid, $removeuserids, ['manual', 'self']);

$includedcourses = $DB->get_records('local_rainmake_backend_careerpath_courses', ['careerpath_id' => $courseid]);
foreach ($includedcourses as $includedcourse) {
    $includedcourseid = (int)$includedcourse->course_id;
    rainmake_manual_enrol_users($includedcourseid, $adduserids);
    rainmake_unenrol_users($includedcourseid, $removeuserids, ['manual']);
}

redirect(new moodle_url('/theme/rainmake/admincareerpaths.php'));
