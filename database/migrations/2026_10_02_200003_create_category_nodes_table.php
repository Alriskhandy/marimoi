<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Skema v3 §5.2 — pohon subkategori ber-ltree di bawah categories_v3.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('category_nodes', function (Blueprint $table) {
            $table->uuid('id')->default(DB::raw('gen_random_uuid()'))->primary();
            $table->uuid('category_id');
            $table->uuid('parent_id')->nullable();
            $table->string('name', 150);
            $table->string('slug', 160);
            $table->text('description')->nullable();
            $table->smallInteger('depth')->default(1);
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('updated_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->foreign('category_id')->references('id')->on('categories_v3')->cascadeOnDelete();
            $table->unique(['id', 'category_id'], 'uq_category_nodes_id_category');
        });

        DB::statement('ALTER TABLE category_nodes ADD COLUMN path ltree NOT NULL');
        DB::statement('ALTER TABLE category_nodes ADD CONSTRAINT ck_category_nodes_depth CHECK (depth >= 1)');
        DB::statement('ALTER TABLE category_nodes ADD CONSTRAINT ck_category_nodes_not_self CHECK (parent_id IS DISTINCT FROM id)');
        DB::statement(<<<'SQL'
            ALTER TABLE category_nodes ADD CONSTRAINT fk_category_nodes_parent
                FOREIGN KEY (parent_id, category_id)
                REFERENCES category_nodes (id, category_id) ON DELETE CASCADE
            SQL);

        DB::statement(<<<'SQL'
            CREATE UNIQUE INDEX uq_category_nodes_sibling_slug
                ON category_nodes (category_id, COALESCE(parent_id, '00000000-0000-0000-0000-000000000000'::uuid), slug)
                WHERE deleted_at IS NULL
            SQL);
        DB::statement('CREATE INDEX ix_category_nodes_path ON category_nodes USING gist (path)');
        DB::statement('CREATE INDEX ix_category_nodes_parent ON category_nodes (parent_id)');
    }

    public function down(): void
    {
        Schema::dropIfExists('category_nodes');
    }
};
