<?php

use App\Support\Locale;
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
        Schema::create('pages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->json('title');
            $table->string('slug')->unique();
            $table->json('content')->nullable();
            $table->json('description')->nullable();
            $table->json('puck_data')->nullable();
            $table->string('status', 20)->default('active');
            $table->string('template')->default('puck');
            $table->string('type', 20)->default('page');
            $table->foreignId('product_id')->nullable()->constrained('products')->nullOnDelete();
            $table->foreignId('post_id')->nullable()->constrained('posts')->nullOnDelete();
            $table->foreignId('category_id')->nullable()->constrained('categories')->nullOnDelete();
            $table->string('og_image')->nullable();
            $table->unsignedInteger('sort_order')->default(0);
            $table->json('seo_title')->nullable();
            $table->json('seo_description')->nullable();
            $table->json('og_title')->nullable();
            $table->json('og_description')->nullable();
            $table->json('twitter_title')->nullable();
            $table->json('twitter_description')->nullable();
            $table->string('twitter_image')->nullable();
            $table->boolean('no_index')->default(false);
            $table->boolean('no_follow')->default(false);
            $table->string('canonical_base')->nullable();
            $table->string('canonical_slug')->nullable();
            $table->json('constant')->nullable();
            $table->timestamps();
        });

        $this->wrapExistingSeoCopyInThePrimaryLocale();
    }

    /**
     * Backfills the SEO columns into the per-locale shape declared above.
     *
     * The SEO copy is translatable because a Bengali page needs a Bengali meta
     * title and description; sharing the English one across both locales is what
     * a site gets penalised for. The images, the canonical parts and the two
     * switches stay single-valued — a URL and a boolean have nothing to translate.
     *
     * On a fresh install this is a no-op: there is no data yet. On an existing one
     * it is the whole reason the copy survives, because a plain string left in a
     * json column is read back by spatie/laravel-translatable as a broken payload
     * and the page silently loses its meta title. The value is filed under the
     * primary locale only and never copied into the others — an English title is
     * not a Bengali translation, and SeoResolver falls back to the primary locale
     * until an admin writes a real one.
     */
    protected function wrapExistingSeoCopyInThePrimaryLocale(): void
    {
        $primary = Locale::primary();

        $columns = [
            'seo_title', 'seo_description', 'og_title',
            'og_description', 'twitter_title', 'twitter_description',
        ];

        foreach ($columns as $column) {
            DB::table('pages')
                ->whereNotNull($column)
                ->orderBy('id')
                ->chunkById(200, function ($pages) use ($column, $primary) {
                    foreach ($pages as $page) {
                        DB::table('pages')->where('id', $page->id)->update([
                            $column => json_encode([$primary => $page->{$column}]),
                        ]);
                    }
                });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('pages');
    }
};
