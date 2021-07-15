<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCompaniesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('companies', function (Blueprint $table) {
            $table->bigInteger('id', true);
            $table->string('name')->nullable()->default('');
            $table->string('address', 100)->default('');
            $table->string('email')->nullable();
            $table->string('phone', 20);
            $table->string('adminName')->nullable()->default('');
            $table->string('logo')->nullable()->default('');
            $table->bigInteger('currentBytes')->nullable();
            $table->bigInteger('maxBytes')->nullable();
            $table->boolean('deleted')->nullable()->default(0);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('companies');
    }
}
