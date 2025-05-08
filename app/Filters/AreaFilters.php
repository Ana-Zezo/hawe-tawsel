<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class AreaFilters extends QueryFilters
{
    protected array $allowedFilters = ['name_en', 'name_ar'];

    protected array $columnSearch = [];
}