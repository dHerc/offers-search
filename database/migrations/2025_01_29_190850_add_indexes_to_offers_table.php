<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::statement("CREATE INDEX offer_text_idx ON offers USING GIN (to_tsvector('english', category || title || features || description || details))");
        DB::statement("CREATE INDEX offer_embeddings_idx ON offers USING hnsw (embeddings vector_cosine_ops);");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::statement("DROP INDEX offer_text_idx");
        DB::statement("DROP INDEX offer_embeddings_idx");
    }
};
