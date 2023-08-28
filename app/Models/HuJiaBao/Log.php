<?php

namespace App\Models\HuJiaBao;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Log extends Model
{
    use HasFactory;

    protected $table = 'hjb_logs';

    protected $fillable = [
        'type', 'url', 'status', 'request', 'response', 'created_at', 'updated_at'
    ];
}
