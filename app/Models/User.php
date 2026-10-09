<?php

declare(strict_types=1);

namespace App\Models;

use LocalPHP\Model\Model;

class User extends Model
{
    protected ?string $table = 'users';

    protected string $primaryKey = 'id';

    protected array $fillable = [
        'name',
        'email',
        'password',
        'status',
    ];

    protected array $guarded = [
        'id',
    ];

    protected bool $timestamps = true;
}