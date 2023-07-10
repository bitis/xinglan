<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Approver extends Model
{
    use HasFactory;

    /**
     * 审核
     */
    const TYPE_CHECKER = 1;

    /**
     * 复核
     */
    const TYPE_REVIEWER = 2;

    /**
     * 抄送
     */
    const TYPE_RECEIVER = 3;

    const STEP_CHECKER = 1;
    const STEP_REVIEWER = 2;
    const STEP_RECEIVER = 3;

    protected $fillable = ['approval_option_id', 'type', 'user_id'];
}
