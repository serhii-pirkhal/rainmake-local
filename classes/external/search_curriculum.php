<?php

namespace local_rainmake_backend\external;

use context_system;
use core_external\external_api;
use core_external\external_function_parameters;
use core_external\external_multiple_structure;
use core_external\external_single_structure;
use core_external\external_value;

/**
 * Search curriculum items for Assign Task: courses, modules (sessions), lectures.
 *
 * Returns a unified list of items with a "type" field:
 * - course: {type, courseid, title, subtitle, courseimageurl?}
 * - module: {type, courseid, moduleid, title, subtitle}
 * - lecture:{type, courseid, moduleid, lectureid, title, subtitle}
 *
 * Best-practice for current schema: selecting module/lecture selects its parent course.
 */
class search_curriculum extends external_api {

    public static function execute_parameters(): external_function_parameters {
        return new external_function_parameters([
            'query' => new external_value(PARAM_RAW, 'Search query', VALUE_REQUIRED),
            'limit' => new external_value(PARAM_INT, 'Maximum results', VALUE_DEFAULT, 10),
        ]);
    }

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
        $limit = max(1, (int)$params['limit']);
        $dbman = $DB->get_manager();
        $results = [];

        // 1) Courses (core table - always run).
        $sql = "SELECT id, fullname, shortname
                  FROM {course}
                 WHERE id > 1
                   AND ( " . $DB->sql_like('fullname', ':clike0', false, false, false) . "
                      OR " . $DB->sql_like('shortname', ':clike1', false, false, false) . " )
              ORDER BY fullname";
        $courses = $DB->get_records_sql($sql, ['clike0' => $like, 'clike1' => $like], 0, $limit);
        foreach ($courses as $c) {
            $urlstring = '';
            try {
                $course = get_course($c->id);
                $imageurl = \core_course\external\course_summary_exporter::get_course_image($course);
                if ($imageurl) {
                    $urlstring = $imageurl instanceof \moodle_url ? $imageurl->out(false) : (string)$imageurl;
                }
            } catch (\Throwable $e) {
                // Continue without image if course or image fails.
            }
            $results[] = [
                'type' => 'course',
                'courseid' => (int)$c->id,
                'moduleid' => 0,
                'lectureid' => 0,
                'title' => $c->fullname,
                'subtitle' => $c->shortname ?? '',
                'courseimageurl' => $urlstring,
            ];
        }

        // 2) Modules (sessions) - only if table exists.
        $remaining = $limit - count($results);
        if ($remaining > 0 && $dbman->table_exists('local_rainmake_backend_sessions')) {
            try {
                $sql = "SELECT s.id AS moduleid, s.courseid AS courseid, s.title AS title, c.fullname AS coursename, c.shortname AS courseshort
                          FROM {local_rainmake_backend_sessions} s
                          JOIN {course} c ON c.id = s.courseid
                         WHERE " . $DB->sql_like('s.title', ':slike0', false, false, false) . "
                      ORDER BY s.id DESC";
                $sessions = $DB->get_records_sql($sql, ['slike0' => $like], 0, $remaining);
                foreach ($sessions as $s) {
                    $results[] = [
                        'type' => 'module',
                        'courseid' => (int)$s->courseid,
                        'moduleid' => (int)$s->moduleid,
                        'lectureid' => 0,
                        'title' => $s->title,
                        'subtitle' => $s->coursename,
                        'courseimageurl' => '',
                    ];
                }
            } catch (\Exception $e) {
                // Skip modules on error.
            }
        }

        // 3) Lectures - only if table exists.
        $remaining = $limit - count($results);
        if ($remaining > 0 && $dbman->table_exists('local_rainmake_backend_lectures')) {
            try {
                $sql = "SELECT l.id AS lectureid, l.sessionid AS moduleid, s.courseid AS courseid, l.title AS title, c.fullname AS coursename
                          FROM {local_rainmake_backend_lectures} l
                          JOIN {local_rainmake_backend_sessions} s ON s.id = l.sessionid
                          JOIN {course} c ON c.id = s.courseid
                         WHERE " . $DB->sql_like('l.title', ':llike0', false, false, false) . "
                      ORDER BY l.id DESC";
                $lectures = $DB->get_records_sql($sql, ['llike0' => $like], 0, $remaining);
                foreach ($lectures as $l) {
                    $results[] = [
                        'type' => 'lecture',
                        'courseid' => (int)$l->courseid,
                        'moduleid' => (int)$l->moduleid,
                        'lectureid' => (int)$l->lectureid,
                        'title' => $l->title,
                        'subtitle' => $l->coursename,
                        'courseimageurl' => '',
                    ];
                }
            } catch (\Exception $e) {
                // Skip lectures on error.
            }
        }

        return $results;
    }

    public static function execute_returns(): external_multiple_structure {
        return new external_multiple_structure(
            new external_single_structure([
                'type' => new external_value(PARAM_ALPHA, 'Item type: course/module/lecture'),
                'courseid' => new external_value(PARAM_INT, 'Parent course id'),
                'moduleid' => new external_value(PARAM_INT, 'Module/session id (0 for course)'),
                'lectureid' => new external_value(PARAM_INT, 'Lecture id (0 for course/module)'),
                'title' => new external_value(PARAM_TEXT, 'Primary label'),
                'subtitle' => new external_value(PARAM_TEXT, 'Secondary label'),
                'courseimageurl' => new external_value(PARAM_URL, 'Course image URL', VALUE_OPTIONAL),
            ])
        );
    }
}

