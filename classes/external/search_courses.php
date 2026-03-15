<?php

namespace local_rainmake_backend\external;

use context_system;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

class search_courses extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'query' => new external_value(PARAM_RAW, 'Search query', VALUE_REQUIRED),
            'limit' => new external_value(PARAM_INT, 'Maximum results', VALUE_DEFAULT, 10),
        ]);
    }

    /** Search courses by fullname or shortname (for Assign Task course search). */
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
        $paramsdb = ['like0' => $like, 'like1' => $like];

        $sql = "SELECT id, fullname, shortname
                  FROM {course}
                 WHERE id > 1
                   AND ( " . $DB->sql_like('fullname', ':like0', false, false, false) . "
                      OR " . $DB->sql_like('shortname', ':like1', false, false, false) . " )
              ORDER BY fullname";

        $courses = $DB->get_records_sql($sql, $paramsdb, 0, $params['limit']);

        $results = [];
        foreach ($courses as $c) {
            $course = get_course($c->id);
            $imageurl = \core_course\external\course_summary_exporter::get_course_image($course);
            $urlstring = '';
            if ($imageurl) {
                $urlstring = $imageurl instanceof \moodle_url ? $imageurl->out(false) : (string) $imageurl;
            }
            $results[] = [
                'id' => (int)$c->id,
                'fullname' => $c->fullname,
                'shortname' => $c->shortname,
                'courseimageurl' => $urlstring,
            ];
        }

        return $results;
    }

    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                'id' => new external_value(PARAM_INT, 'Course id'),
                'fullname' => new external_value(PARAM_TEXT, 'Full name'),
                'shortname' => new external_value(PARAM_TEXT, 'Short name'),
                'courseimageurl' => new external_value(PARAM_URL, 'Course image URL', VALUE_OPTIONAL),
            ])
        );
    }
}
