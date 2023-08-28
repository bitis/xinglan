<?php

namespace App\Models;

use App\Models\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AppraisalTask extends Model
{
    use HasFactory, DefaultDatetimeFormat;

    protected $table = 'hjb_appraisal_tasks';

    protected $fillable = [];

}
