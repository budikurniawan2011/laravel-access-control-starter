<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InstallationState extends Model
{
    protected $fillable = ['setup_completed'];

    protected function casts(): array
    {
        return [
            'setup_completed' => 'boolean',
        ];
    }
}
