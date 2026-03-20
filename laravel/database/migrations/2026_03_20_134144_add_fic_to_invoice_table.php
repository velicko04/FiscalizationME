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
    Schema::table('Invoice', function (Blueprint $table) {
        $table->string('fic')->nullable()->after('iic_signature');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('invoice', function (Blueprint $table) {
            //
        });
    }
};
