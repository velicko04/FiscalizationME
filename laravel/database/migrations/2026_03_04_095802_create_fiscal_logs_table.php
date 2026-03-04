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
    Schema::create('fiscal_logs', function (Blueprint $table) {
        $table->id();

        $table->integer('invoice_id'); // umjesto unsignedBigInteger

        $table->longText('request_xml')->nullable();
        $table->longText('response_xml')->nullable();

        $table->string('status')->nullable();
        $table->longText('error_message')->nullable();

        $table->timestamps();
    });
}

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('fiscal_logs');
    }
};
