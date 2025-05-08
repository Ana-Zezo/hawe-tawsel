<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class OrderFilters extends QueryFilters
{
    // protected array $allowedFilters = ['phone_receiver'];

    protected array $columnSearch = ['phone_receiver'];
}