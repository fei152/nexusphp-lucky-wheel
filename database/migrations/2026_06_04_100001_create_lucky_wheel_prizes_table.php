<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('plugin_lucky_wheel_prizes', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('description', 255)->default('');
            $table->string('reward_type', 20)->default('bonus');
            $table->decimal('reward_value', 12, 2)->default(0);
            $table->text('reward_content')->nullable();
            $table->decimal('probability', 8, 4)->default(0);
            $table->integer('stock')->nullable();
            $table->integer('sort')->default(0);
            $table->boolean('enabled')->default(true);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('plugin_lucky_wheel_prizes');
    }
};
