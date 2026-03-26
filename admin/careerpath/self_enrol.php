<?php

require_once(__DIR__ . '/../../../../config.php');
require_login();
require_sesskey();

function rainmake_get_or_create_manual_instance(int $courseid): stdClass {
    global $DB;

    $instances = enrol_get_instances($courseid, true);
    foreach ($instances as $instance) {
        if ($instance->enrol === 'manual') {
            return $instance;
        }
    }

    $plugin = enrol_get_plugin('manual');
    if (!$plugin) {
        throw new moodle_exception('Manual enrolment plugin is not enabled.');
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

$careerpathid = required_param('id', PARAM_INT);

$careerpath = get_course($careerpathid);
$context = context_course::instance($careerpathid);

if (is_enrolled($context, $USER->id)) {
    redirect(new moodle_url('/theme/rainmake/careerpath.php', ['id' => $careerpathid]));
}

$instances = enrol_get_instances($careerpathid, true);
$selfinstance = null;

foreach ($instances as $instance) {
    if ($instance->enrol === 'self' && (int)$instance->status === ENROL_INSTANCE_ENABLED) {
        $selfinstance = $instance;
        break;
    }
}

if (!$selfinstance) {
    throw new moodle_exception('Self enrolment is not available for this career path.');
}

$plugin = enrol_get_plugin('self');
if (!$plugin) {
    throw new moodle_exception('Self enrolment plugin is not enabled.');
}

$roleid = !empty($selfinstance->roleid) ? (int)$selfinstance->roleid : 5;
$plugin->enrol_user($selfinstance, $USER->id, $roleid);

$includedcourses = $DB->get_records('local_rainmake_backend_careerpath_courses', ['careerpath_id' => $careerpathid]);
foreach ($includedcourses as $includedcourse) {
    $includedcourseid = (int)$includedcourse->course_id;
    $includedcontext = context_course::instance($includedcourseid);
    if (is_enrolled($includedcontext, $USER->id)) {
        continue;
    }

    $manualinstance = rainmake_get_or_create_manual_instance($includedcourseid);
    $manualplugin = enrol_get_plugin('manual');
    if (!$manualplugin) {
        throw new moodle_exception('Manual enrolment plugin is not enabled.');
    }

    $manualroleid = !empty($manualinstance->roleid) ? (int)$manualinstance->roleid : 5;
    $manualplugin->enrol_user($manualinstance, $USER->id, $manualroleid);
}

redirect(new moodle_url('/theme/rainmake/careerpath.php', ['id' => $careerpathid]));
