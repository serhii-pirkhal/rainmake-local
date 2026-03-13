<?php

namespace local_rainmake_backend\external;

use context_system;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

class search_users extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'query' => new external_value(PARAM_RAW, 'Search query', VALUE_REQUIRED),
            'limit' => new external_value(PARAM_INT, 'Maximum results', VALUE_DEFAULT, 10),
        ]);
    }

    /** Search users by firstname, lastname, username, or email (for Assign Task student search). */
    public static function execute(string $query, int $limit = 10): array {
        global $DB;

        self::validate_context(context_system::instance());

        $params = self::validate_parameters(self::execute_parameters(), [
            'query' => $query,
            'limit' => $limit,
        ]);

        $q = trim($params['query']);
        if ($q === '') {
            return [];
        }

        $like = '%' . $DB->sql_like_escape($q) . '%';

        $fields = ['firstname', 'lastname', 'username', 'email'];
        $conditions = [];
        $paramsdb = ['guestid' => (int)guest_user()->id];
        $i = 0;
        foreach ($fields as $field) {
            $key = 'like' . $i;
            $conditions[] = $DB->sql_like($field, ':' . $key, false, false, false);
            $paramsdb[$key] = $like;
            $i++;
        }

        $where = '(' . implode(' OR ', $conditions) . ')';

        $sql = "SELECT id, firstname, lastname, username, email, picture, imagealt
                  FROM {user}
                 WHERE deleted = 0
                   AND id <> :guestid
                   AND $where
              ORDER BY lastname, firstname";

        $users = $DB->get_records_sql($sql, $paramsdb, 0, $params['limit']);

        global $PAGE;
        $results = [];
        foreach ($users as $u) {
            $profileimageurl = '';
            if (!empty($u->picture) && $PAGE) {
                $userpicture = new \user_picture($u);
                $userpicture->size = 100;
                $profileimageurl = $userpicture->get_url($PAGE)->out(false);
            }
            $results[] = [
                'id'               => (int)$u->id,
                'firstname'        => $u->firstname,
                'lastname'         => $u->lastname,
                'username'         => $u->username,
                'email'            => $u->email,
                'profileimageurl'  => $profileimageurl,
            ];
        }

        return $results;
    }

    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                'id'               => new external_value(PARAM_INT, 'User id'),
                'firstname'        => new external_value(PARAM_TEXT, 'First name'),
                'lastname'         => new external_value(PARAM_TEXT, 'Last name'),
                'username'         => new external_value(PARAM_TEXT, 'Username'),
                'email'            => new external_value(PARAM_TEXT, 'Email'),
                'profileimageurl'  => new external_value(PARAM_URL, 'Profile image URL', VALUE_OPTIONAL),
            ])
        );
    }
}

