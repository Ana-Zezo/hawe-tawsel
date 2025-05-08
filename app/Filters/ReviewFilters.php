<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class ReviewFilters extends QueryFilters
{
    protected array $allowedFilters = ['status', 'order_id'];

    protected array $columnSearch = [];
}