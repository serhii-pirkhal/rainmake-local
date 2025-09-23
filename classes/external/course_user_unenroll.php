<?php

namespace local_rainmake_backend\external;

use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_single_structure;
use core_external\external_value;

class course_user_unenroll extends external_api
{

    public static function execute_parameters(){
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'Course ID'),
            'userid' => new external_value(PARAM_INT, 'User ID'),
        ]);
    }

    public static function execute($courseid, $userid) {
        $enrol = enrol_get_plugin('manual');
        if ($enrol) {
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
                    'enrol' => 'manual',
                    'status' => ENROL_INSTANCE_ENABLED,
                    'courseid' => $courseid,
                    'roleid' => 5,
                ];
                $instanceid = $DB->insert_record('enrol', (object)$fields);
                $manualinstance = $DB->get_record('enrol', ['id' => $instanceid]);
            }

            $enrol->unenrol_user($manualinstance, $userid);
        }
        return [
            'success' => true,
        ];
    }

    public static function execute_returns() {
        return new external_single_structure([
            'success' => new external_value(PARAM_BOOL, 'True if operation succeeded'),
        ]);
    }
}

