<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
return new class extends Migration {
 public function up():void {
  Schema::create('records', function(Blueprint $table) {
   $table->id(); $table->string('section',50)->index(); $table->string('title'); $table->string('subtitle')->nullable();
   $table->string('status',30)->default('draft')->index(); $table->decimal('amount',14,2)->nullable();
   $table->date('date')->nullable(); $table->json('payload')->nullable(); $table->timestamps();
  });
 }
 public function down():void { Schema::dropIfExists('records'); }
};
