<?php

namespace App\Filters;

use Essa\APIToolKit\Filters\QueryFilters;

class TransactionFilters extends QueryFilters
{
    protected array $allowedFilters = ['product_id'];

    protected array $columnSearch = [];
}