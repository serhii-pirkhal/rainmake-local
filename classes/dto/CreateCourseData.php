<?php

namespace local_rainmake_backend\dto;

class CreateCourseData{
    public bool $delete = false;
    public string $fullname;
    public string $category;
    public ?int $id;
}
