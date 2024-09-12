<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDataTables extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        // Check if 'source_data' table does not exist before creating
        if (!Schema::hasTable('source_data')) {
            Schema::create('source_data', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description');
                $table->timestamp('created_at')->useCurrent();
            });
        }

        // Check if 'destination_data' table does not exist before creating
        if (!Schema::hasTable('destination_data')) {
            Schema::create('destination_data', function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->text('description');
                $table->timestamp('created_at')->useCurrent();
            });
        }
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('source_data');
        Schema::dropIfExists('destination_data');
    }
}
