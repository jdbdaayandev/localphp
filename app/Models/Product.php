<?php

declare(strict_types=1);

namespace App\Models;

use LocalPHP\Model\Model;

class Product extends Model
{
    /**
     * Database table associated with this model.
     *
     * Set this explicitly if your ORM does not infer table names.
     */
    protected string $table = 'products';
}
