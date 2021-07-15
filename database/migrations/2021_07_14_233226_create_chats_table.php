<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateChatsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('chats', function (Blueprint $table) {
            $table->bigInteger('idChat', true);
            $table->string('codeChat')->nullable();
            $table->timestamp('startDate')->useCurrentOnUpdate()->nullable();
            $table->timestamp('endingDate')->useCurrentOnUpdate()->nullable();
            $table->longText('Participants')->nullable();
            $table->string('type')->nullable();
            $table->string('state')->nullable();
            $table->bigInteger('Company')->nullable();
            $table->integer('numberMessage')->nullable();
            $table->text('lastMessage')->nullable();
            $table->integer('numberRecommedations')->nullable();
            $table->integer('idNotification')->nullable();
            $table->integer('idError')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('chats');
    }
}
