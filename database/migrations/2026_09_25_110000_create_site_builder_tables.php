<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('content_type_fields', function (Blueprint $table) {
            $table->boolean('searchable')->default(false)->after('is_system');
        });

        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->string('slug');
            $table->longText('body')->nullable();
            $table->string('status', 20)->default('draft');
            $table->string('seo_title')->nullable();
            $table->text('seo_description')->nullable();
            $table->timestamps();

            $table->unique(['tenant_id', 'slug']);
        });

        Schema::create('menus', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('location', 20);
            $table->timestamps();

            $table->unique(['tenant_id', 'location']);
        });

        Schema::create('menu_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('menu_id')->constrained()->cascadeOnDelete();
            $table->foreignId('parent_id')->nullable()->constrained('menu_items')->nullOnDelete();
            $table->string('label');
            $table->string('type', 30);
            $table->string('url')->nullable();
            $table->unsignedBigInteger('target_id')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('enabled')->default(true);
            $table->boolean('open_new_tab')->default(false);
            $table->timestamps();

            $table->index(['menu_id', 'sort_order']);
        });

        Schema::create('layout_blocks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('region', 20);
            $table->unsignedInteger('row')->default(1);
            $table->unsignedInteger('sort_order')->default(0);
            $table->string('component', 40);
            $table->unsignedTinyInteger('col_desktop')->default(4);
            $table->unsignedTinyInteger('col_tablet')->default(2);
            $table->unsignedTinyInteger('col_mobile')->default(1);
            $table->json('settings')->nullable();
            $table->boolean('enabled')->default(true);
            $table->timestamps();

            $table->index(['tenant_id', 'region', 'row', 'sort_order']);
        });

        Schema::create('newsletter_subscribers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tenant_id')->constrained()->cascadeOnDelete();
            $table->string('email');
            $table->timestamps();

            $table->unique(['tenant_id', 'email']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('newsletter_subscribers');
        Schema::dropIfExists('layout_blocks');
        Schema::dropIfExists('menu_items');
        Schema::dropIfExists('menus');
        Schema::dropIfExists('pages');
        Schema::table('content_type_fields', function (Blueprint $table) {
            $table->dropColumn('searchable');
        });
    }
};
