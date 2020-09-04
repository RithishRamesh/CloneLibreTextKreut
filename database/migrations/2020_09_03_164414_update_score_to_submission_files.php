<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class UpdateScoreToSubmissionFiles extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        DB::connection()->getDoctrineSchemaManager()->getDatabasePlatform()->registerDoctrineTypeMapping('enum', 'string');
        Schema::table('submission_files', function (Blueprint $table) {
                $table->unsignedDecimal('score',5,2)->change();

        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('submission_files', function (Blueprint $table) {
            //
        });
    }
}
