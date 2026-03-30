<?php

namespace local_rainmake_backend\external;

use context_course;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

class get_careerpath_enrolment_users extends external_api
{
    public static function execute_parameters() {
        return new external_function_parameters([
            'courseid' => new external_value(PARAM_INT, 'career path course id'),
        ]);
    }

    public static function execute($courseid) {
        global $DB;

        $params = [
            'courseid' => $courseid,
            'roleid' => 5,
            'contextlevel' => CONTEXT_COURSE,
        ];

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

        $enrolledids = $DB->get_fieldset_sql($sql, $params);
        $enrolledids = array_map('intval', $enrolledids);
        $enrolledmap = array_fill_keys($enrolledids, true);

        $users = $DB->get_records('user', ['deleted' => 0], 'firstname ASC, lastname ASC, email ASC');

        $result = [
            'enrolledusers' => [],
            'availableusers' => [],
        ];

        foreach ($users as $user) {
            $usercontext = \context_user::instance($user->id);
            $pictureurl = \moodle_url::make_pluginfile_url(
                $usercontext->id,
                'user',
                'icon',
                null,
                '/',
                'f2'
            )->out(false);

            $record = [
                'id' => (int)$user->id,
                'username' => $user->username,
                'firstname' => $user->firstname,
                'lastname' => $user->lastname,
                'email' => $user->email,
                'picture' => $pictureurl,
                'enrolled' => !empty($enrolledmap[$user->id]),
            ];

            if ($record['enrolled']) {
                $result['enrolledusers'][] = $record;
            } else {
                $result['availableusers'][] = $record;
            }
        }

        return $result;
    }

    public static function execute_returns() {
        $userstructure = new external_single_structure([
            'id' => new external_value(PARAM_INT, 'ID of the user'),
            'username' => new external_value(PARAM_TEXT, 'Username'),
            'firstname' => new external_value(PARAM_TEXT, 'Firstname'),
            'lastname' => new external_value(PARAM_TEXT, 'Lastname'),
            'email' => new external_value(PARAM_TEXT, 'Email'),
            'picture' => new external_value(PARAM_TEXT, 'Picture'),
            'enrolled' => new external_value(PARAM_BOOL, 'Whether the user is currently enrolled'),
        ]);

        return new external_single_structure([
            'enrolledusers' => new external_multiple_structure($userstructure),
            'availableusers' => new external_multiple_structure($userstructure),
        ]);
    }
}
