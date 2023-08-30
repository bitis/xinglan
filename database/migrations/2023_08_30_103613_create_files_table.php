<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('files', function (Blueprint $table) {
            $table->id();
            $table->integer('@pk');
            $table->integer('@type');
            $table->integer('AttachFileId');
            $table->char('AttachType', 2);
            $table->integer('BusinessObjectId');
            $table->string('DisplayName');
            $table->bigInteger('DmsDocId');
            $table->string('FileExt', 10);
            $table->integer('FileSize');
            $table->boolean('IsDeleted');
            $table->boolean('IsImage');
            $table->boolean('OrgFileName');
            $table->string('Path', 5);
            $table->integer('Sort');
            $table->timestamp('UploadDate');
            $table->bigInteger('UploadUserId');
            $table->string('url');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('files');
    }
};
