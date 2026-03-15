<?php

namespace local_rainmake_backend;
require_once(__DIR__ . '/../../../config.php');
require_login();

class Assignment
{
    private \moodle_database $DB;
    private \stdClass $CFG;

    public function __construct()
    {
        global $DB;
        global $CFG;
        $this->DB = $DB;
        $this->CFG = $CFG;
    }

    /**
     * Get all distinct students that appear in any task (for filter dropdown).
     */
    public function getFilterStudents(): array
    {
        $sql = "SELECT DISTINCT u.id, u.firstname, u.lastname, u.picture
                  FROM {assignment_tasks_students} ts
                  JOIN {user} u ON u.id = ts.user_id
                 ORDER BY u.lastname, u.firstname";
        $users = $this->DB->get_records_sql($sql);
        $result = [];
        global $PAGE;
        foreach ($users as $u) {
            $profileimageurl = '';
            if (!empty($u->picture) && $PAGE) {
                $userpicture = new \user_picture($u);
                $userpicture->size = 100;
                $profileimageurl = $userpicture->get_url($PAGE)->out(false);
            }
            $result[] = (object)[
                'id' => (int)$u->id,
                'firstname' => $u->firstname,
                'lastname' => $u->lastname,
                'profileimageurl' => $profileimageurl,
                'initials' => self::user_initials($u),
            ];
        }
        return $result;
    }

    public function getTasksCount(string $search = '', ?int $studentid = null): int
    {
        $params = [];
        $joins = "LEFT JOIN {course} c ON c.id = t.course_id";
        $where = "1=1";

        if ($studentid > 0) {
            $joins .= " INNER JOIN {assignment_tasks_students} ts ON ts.task_id = t.id AND ts.user_id = :studentid";
            $params['studentid'] = $studentid;
        }

        $search = trim($search);
        if ($search !== '') {
            $liketerm = '%' . $this->DB->sql_like_escape($search) . '%';
            $params['searchname'] = $liketerm;
            $params['searchfullname'] = $liketerm;
            $where .= " AND (" . $this->DB->sql_like('t.name', ':searchname', false, false, false)
                . " OR " . $this->DB->sql_like('c.fullname', ':searchfullname', false, false, false) . ")";
        }

        $sql = "SELECT COUNT(DISTINCT t.id) AS cnt
                  FROM {assignment_tasks} t
                  $joins
                 WHERE $where";
        $rec = $this->DB->get_record_sql($sql, $params);
        return (int)($rec->cnt ?? 0);
    }

    public function getTasks($page, $perPage, string $search = '', ?int $studentid = null): array
    {
        $offset = ($page - 1) * $perPage;
        $params = [];
        $joins = "LEFT JOIN {course} c ON c.id = t.course_id";
        $where = "1=1";

        if ($studentid > 0) {
            $joins .= " INNER JOIN {assignment_tasks_students} ts ON ts.task_id = t.id AND ts.user_id = :studentid";
            $params['studentid'] = $studentid;
        }

        $search = trim($search);
        if ($search !== '') {
            $liketerm = '%' . $this->DB->sql_like_escape($search) . '%';
            $params['searchname'] = $liketerm;
            $params['searchfullname'] = $liketerm;
            $where .= " AND (" . $this->DB->sql_like('t.name', ':searchname', false, false, false)
                . " OR " . $this->DB->sql_like('c.fullname', ':searchfullname', false, false, false) . ")";
        }

        $sql = "SELECT t.id
                  FROM {assignment_tasks} t
                  $joins
                 WHERE $where
                 ORDER BY t.id DESC";
        $taskrows = $this->DB->get_records_sql($sql, $params, $offset, $perPage);
        $taskids = array_values(array_map(function ($r) {
            return $r->id;
        }, $taskrows));
        if (empty($taskids)) {
            return [];
        }
        $tasks = $this->DB->get_records_list('assignment_tasks', 'id', $taskids);
        // Preserve order and enrich.
        $ordered = [];
        foreach ($taskids as $id) {
            if (isset($tasks[$id])) {
                $ordered[] = $tasks[$id];
            }
        }
        foreach ($ordered as $task) {
            $users = array();
            $userids = array_values($this->DB->get_records('assignment_tasks_students', array('task_id' => $task->id)));
            foreach ($userids as $userid) {
                $u = $this->DB->get_record('user', array('id' => $userid->user_id), 'id, firstname, lastname, picture');
                if (!$u) {
                    continue;
                }
                $profileimageurl = '';
                global $PAGE;
                if (!empty($u->picture) && $PAGE) {
                    $userpicture = new \user_picture($u);
                    $userpicture->size = 100;
                    $profileimageurl = $userpicture->get_url($PAGE)->out(false);
                }
                $users[] = (object)[
                    'id' => (int)$u->id,
                    'firstname' => $u->firstname,
                    'lastname' => $u->lastname,
                    'profileimageurl' => $profileimageurl,
                    'initials' => self::user_initials($u),
                ];
            }
            $task->course = $this->DB->get_record('course', array('id' => $task->course_id), 'fullname, shortname');
            $task->files = array_values($this->DB->get_records('assignment_tasks_files', array('task_id' => $task->id), null, 'filepath'));
            foreach ($task->files as $file) {
                $file->size = round(filesize($this->CFG->dirroot . "/" . $file->filepath) / (1024 * 1024), 1);
                $file->name = basename($this->CFG->dirroot . "/" . $file->filepath);
                // Display name: first part + "…" + last part (so start and extension are visible).
                $len = mb_strlen($file->name);
                $firstChars = 16;
                $lastChars = 16;
                $minLen = $firstChars + $lastChars + 1;
                $file->name_display = ($len > $minLen)
                    ? mb_substr($file->name, 0, $firstChars) . '…' . mb_substr($file->name, -$lastChars)
                    : $file->name;
            }
            $task->users = $users;
            $task->user_ids = implode(',', array_map(function($u) { return $u->id; }, $users));
            // Aggregate data for template.
            $task->studentscount = count($users);
            $task->filescount = count($task->files);
            $task->hasfeedback = !empty(trim((string)($task->feedback ?? '')));
        }
        return $ordered;
    }

    private static function user_initials(\stdClass $u): string {
        $f = trim($u->firstname ?? '');
        $l = trim($u->lastname ?? '');
        if ($f !== '' && $l !== '') {
            return strtoupper(mb_substr($f, 0, 1) . mb_substr($l, 0, 1));
        }
        if ($f !== '') {
            return strtoupper(mb_substr($f, 0, 2));
        }
        return '?';
    }
}
