<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateIranCitiesTable extends Migration
{
    /**
     * Migration for shahr
     *
     * Run the migrations.
     *
     * This table is equal to shahr in farsi
     *
     * @return void
     */
    public function up()
    {
        Schema::create('iran_cities', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->unsignedInteger('province_id');
            // county_id / sector_id are kept (the package CSV import populates them) but we only
            // model the province relationship, so they are nullable and carry no foreign keys to
            // the counties/sectors tables (which we intentionally do not publish or import).
            $table->unsignedInteger('county_id')->nullable();
            $table->unsignedInteger('sector_id')->nullable();
            $table->string('code', 50)->unique();
            $table->string('short_code', 20);
            $table->boolean('status')->default(1);

            $table->foreign('province_id')->references('id')->on('iran_provinces')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('iran_cities');
    }
}
