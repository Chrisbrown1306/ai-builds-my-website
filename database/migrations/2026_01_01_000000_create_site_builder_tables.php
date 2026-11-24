<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void {
        Schema::create('projects', function (Blueprint $t) {
            $t->id(); $t->uuid('uuid')->unique();
            $t->string('business_name'); $t->string('city')->nullable();
            $t->string('entity_hint')->nullable();        // owner's answer to "which business?"
            $t->string('status')->default('draft');       // draft|review|generated|published
            $t->string('slug')->nullable()->unique();
            $t->timestamp('published_at')->nullable();
            $t->timestamps();
        });
        Schema::create('sources', function (Blueprint $t) {
            $t->id(); $t->foreignId('project_id')->constrained()->cascadeOnDelete();
            $t->string('type');                           // file|link|web|user|image
            $t->string('label');
            $t->longText('content')->nullable();          // text (or storage path for images)
            $t->string('status')->default('ok');          // ok|flagged|blocked|failed
            $t->string('note')->nullable();
            $t->timestamps();
        });
        Schema::create('facts', function (Blueprint $t) {
            $t->id(); $t->foreignId('project_id')->constrained()->cascadeOnDelete();
            $t->foreignId('source_id')->nullable()->constrained()->nullOnDelete(); // null = told by owner
            $t->string('field'); $t->text('value');
            $t->string('status')->default('confirmed');   // confirmed|rejected
            $t->timestamps();
        });
        Schema::create('questions', function (Blueprint $t) {
            $t->id(); $t->foreignId('project_id')->constrained()->cascadeOnDelete();
            $t->string('kind');                           // disambiguation|conflict|missing
            $t->string('field')->nullable();
            $t->text('prompt'); $t->json('options')->nullable(); $t->text('answer')->nullable();
            $t->timestamps();
        });
        Schema::create('sections', function (Blueprint $t) {
            $t->id(); $t->foreignId('project_id')->constrained()->cascadeOnDelete();
            $t->string('type'); $t->unsignedInteger('position')->default(0); $t->json('content');
            $t->timestamps();
        });
    }
    public function down(): void { foreach (['sections','questions','facts','sources','projects'] as $t) Schema::dropIfExists($t); }
};
