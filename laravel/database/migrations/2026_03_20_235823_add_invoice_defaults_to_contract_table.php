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
    Schema::table('Contract', function (Blueprint $table) {
        $table->string('default_type_of_invoice')->default('NONCASH');
        $table->string('default_payment_method')->default('ACCOUNT');
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contract', function (Blueprint $table) {
            //
        });
    }
};
