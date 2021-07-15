<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateParticipantsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('participants', function (Blueprint $table) {
            $table->bigInteger('idCompetitor', true);
            $table->string('codeCompetitor')->nullable();
            $table->string('idUser')->nullable();
            $table->string('idChat')->nullable();
            $table->string('role')->nullable();
            $table->tinyInteger('ERP')->nullable();
            $table->timestamp('dateAdmission')->useCurrentOnUpdate()->nullable();
            $table->timestamp('departureDate')->useCurrentOnUpdate()->nullable();
            $table->string('state')->nullable();
            $table->tinyInteger('active')->nullable();
            $table->integer('numberMessageSend')->nullable();
            $table->integer('numberMessageRrceived')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('participants');
    }
}
