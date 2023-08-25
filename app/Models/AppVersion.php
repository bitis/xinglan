<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppVersion extends Model
{
    use HasFactory;

    protected $fillable = [
        'app_url', 'apk_url', 'version', 'version_number', 'must_update', 'current_version_number'
    ];
}
