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
        Schema::table('b2b_messages', function (Blueprint $table) {
            $table->string('message_type')->default('text')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('b2b_messages', function (Blueprint $table) {
            $table->enum('message_type', ['text', 'rfq', 'quote_proposal', 'counteroffer', 'po_notice', 'attachment'])->default('text')->change();
        });
    }
};
