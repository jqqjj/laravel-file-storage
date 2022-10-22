<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('file_storages', function (Blueprint $table) {
            $table->increments('file_storage_id');
            $table->string('path')->unique();
            $table->integer('size')->unsigned()->index();
            $table->string('mime')->index();
            $table->string('md5')->index();
            $table->string('crc32')->index();
            $table->timestamps();
            $table->unique(['md5','crc32']);
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('file_storages');
    }
};
