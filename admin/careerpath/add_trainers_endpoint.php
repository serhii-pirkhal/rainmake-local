<?php

use local_rainmake_backend\dto\CreateCourseData;
use local_rainmake_backend\dto\CreateCourseMeta;

require_once(__DIR__ . '/../../../../config.php');
require_once(__DIR__ . '/../course/create_course_action.php');
require_login();
require_sesskey();

$courseid = required_param('careerpath_id', PARAM_INT);
$users = $_POST['users'];

$enrol = enrol_get_plugin('manual');
if (!$enrol) {
    throw new moodle_exception('Manual enrolment plugin not enabled on site');
}

$instances = enrol_get_instances($courseid, true);
$manualinstance = null;
foreach ($instances as $instance) {
    if ($instance->enrol === 'manual') {
        $manualinstance = $instance;
        break;
    }
}

if (!$manualinstance) {
    $fields = [
        'enrol'      => 'manual',
        'status'     => ENROL_INSTANCE_ENABLED,
        'courseid'   => $courseid,
        'roleid'     => 5,
    ];
    $instanceid = $DB->insert_record('enrol', (object)$fields);
    $manualinstance = $DB->get_record('enrol', ['id' => $instanceid]);
}

$teacherroleid = 3;
foreach ($users as $uKey => $user) {
    $enrol->enrol_user($manualinstance, $uKey, $teacherroleid);
}

redirect($_SERVER['HTTP_REFERER']);
