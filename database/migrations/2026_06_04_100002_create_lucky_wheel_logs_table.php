<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plugin_lucky_wheel_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('uid');
            $table->unsignedBigInteger('prize_id');
            $table->decimal('cost', 12, 2)->default(0);
            $table->string('reward_type', 20);
            $table->decimal('reward_value', 12, 2)->default(0);
            $table->text('reward_content')->nullable();
            $table->string('prize_name', 100);
            $table->string('delivery_status', 20)->default('pending');
            $table->timestamps();

            $table->index(['uid', 'created_at']);
            $table->index(['prize_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugin_lucky_wheel_logs');
    }
};
