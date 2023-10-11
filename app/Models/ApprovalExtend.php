<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ApprovalExtend extends Model
{
    use HasFactory;

    protected $fillable = ['approval_option_id', 'start', '', 'end', 'user_id'];
}
