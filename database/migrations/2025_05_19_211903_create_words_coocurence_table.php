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
        Schema::create('word_cooccurrence', function (Blueprint $table) {
            $table->longText('word_a');
            $table->longText('word_b');
            $table->bigInteger('frequency');
            $table->bigInteger('before');
            $table->bigInteger('after');
            $table->primary(['word_a', 'word_b']);
        });

        DB::statement("CREATE INDEX word_cooccurrence_word_a_key ON public.word_cooccurrence USING btree (word_a)");
        DB::statement("CREATE INDEX word_cooccurrence_word_b_key ON public.word_cooccurrence USING btree (word_b)");
        DB::statement("CREATE INDEX word_cooccurrence_frequencies ON public.word_cooccurrence USING btree (frequency DESC)");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::drop('word_cooccurrence');
    }
};
