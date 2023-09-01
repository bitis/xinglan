<?php

namespace App\Models\HuJiaBao;

use App\Models\Traits\DefaultDatetimeFormat;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Files extends Model
{
    use HasFactory, DefaultDatetimeFormat;

    protected $table = 'hjb_files';

    protected $fillable = [
        '@pk',
        '@type',
        'AttachFileId',
        'AttachType',
        'BusinessObjectId',
        'DisplayName',
        'DmsDocId',
        'FileExt',
        'FileSize',
        'IsDeleted',
        'IsImage',
        'OrgFileName',
        'Path',
        'Sort',
        'UploadDate',
        'UploadUserId',
        'url',
        'BusinessNo',
    ];
}
