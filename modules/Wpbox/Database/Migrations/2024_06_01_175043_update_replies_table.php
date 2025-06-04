<?php

use Illuminate\Support\Facades\Schema;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Database\Migrations\Migration;

class UpdateRepliesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('replies', function (Blueprint $table) {
            $table->unsignedBigInteger('flow_id')->nullable();
            $table->foreign('flow_id')->references('id')->on('flows');

            $table->unsignedBigInteger('next_reply_id')->nullable();
            $table->foreign('next_reply_id')->references('id')->on('replies');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('', function (Blueprint $table) {

        });
    }
}
