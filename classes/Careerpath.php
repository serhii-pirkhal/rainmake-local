<?php

namespace local_rainmake_backend;
use moodle_url;
use context_course;

require_once(__DIR__ . '/../../../config.php');
require_login();

class Careerpath
{
    private \moodle_database $DB;

    public function __construct()
    {
        global $DB;
        $this->DB = $DB;
    }

    public function getCareerpaths($page = 1, $perpage = 10, $filters = null, $sort = null, $search = null): array
    {
        global $DB;

        $offset = ($page - 1) * $perpage;
        $where = "id != 0";
        $params = [];

        if (!empty($search)) {
            $where .= " AND title LIKE :search1";
            $params['search1'] = '%' . $search . '%';
        }

        if(empty($sort)) {
            $sort = 'id_desc';
        }
        switch ($sort) {
            case 'name_asc':
                $order = 'title ASC';
                break;
            case 'name_desc':
                $order = 'title DESC';
                break;
            case 'id_desc':
                $order = 'id DESC';
                break;
            case 'id_asc':
                $order = 'id ASC';
                break;
        }


        $sql = "SELECT *
            FROM {local_rainmake_backend_careerpaths} 
            WHERE $where 
            ORDER BY $order";


        $courses = $DB->get_records_sql($sql, $params, $offset, $perpage);

        return array_values($courses);
    }
}