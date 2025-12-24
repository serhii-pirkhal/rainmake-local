<?php

namespace local_rainmake_backend;

class Pagination{
    public function getPagination($url, $page, $perPage, $items): ?array
    {
        $totalPages = ceil($items / $perPage);
        if ($totalPages <= 1){
            return null;
        }
        $pages = array();
        for ($i = 1; $i <= $totalPages; $i++) {
            $active = false;
            if ($i == $page){
                $active = true;
            }
            $pages[] = [
                'url' => $url->out(false, ['page' => $i]),
                'number' => sprintf('%02d', $i),
                'active' => $active
            ];
        }
        return [
            'prev' => $page == 1 ? null : $url->out(false, ['page' => $page - 1]),
            'next' => $page == $totalPages ? null : $url->out(false, ['page' => $page + 1]),
            'pages' => $pages,
        ];
    }
}
