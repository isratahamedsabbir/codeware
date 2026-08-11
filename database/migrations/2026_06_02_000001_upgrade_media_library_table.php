<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('media_library', function (Blueprint $table) {
            $table->uuid('uuid')->nullable()->after('id');
            $table->string('original_filename')->nullable()->after('filename');
            $table->string('file_type')->nullable()->after('mime_type');
            $table->unsignedBigInteger('file_size')->nullable()->after('file_type');
            $table->string('url')->nullable()->after('path');
            $table->string('title')->nullable()->after('url');
            $table->text('caption')->nullable()->after('alt_text');
            $table->text('description')->nullable()->after('caption');
            $table->json('metadata')->nullable()->after('description');
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete()->after('metadata');
            $table->index('file_type');
            $table->index('uploaded_by');
        });

        // Backfill new columns from old data
        DB::table('media_library')->get()->each(function ($row) {
            $fileType = 'document';
            if (str_starts_with($row->mime_type, 'image/')) {
                $fileType = 'image';
            } elseif (str_starts_with($row->mime_type, 'video/')) {
                $fileType = 'video';
            } elseif (str_starts_with($row->mime_type, 'audio/')) {
                $fileType = 'audio';
            }

            DB::table('media_library')->where('id', $row->id)->update([
                'uuid'              => (string) Str::uuid(),
                'original_filename' => $row->original_name,
                'file_type'         => $fileType,
                'file_size'         => $row->size,
                'url'               => \Illuminate\Support\Facades\Storage::disk($row->disk)->url($row->path),
            ]);
        });

        // Make uuid unique after backfill
        Schema::table('media_library', function (Blueprint $table) {
            $table->uuid('uuid')->unique()->change();
        });

        // Drop old columns
        Schema::table('media_library', function (Blueprint $table) {
            $table->dropColumn(['original_name', 'size']);
        });
    }

    public function down(): void
    {
        Schema::table('media_library', function (Blueprint $table) {
            $table->string('original_name')->nullable()->after('filename');
            $table->unsignedBigInteger('size')->nullable()->after('disk');
        });

        DB::table('media_library')->get()->each(function ($row) {
            DB::table('media_library')->where('id', $row->id)->update([
                'original_name' => $row->original_filename,
                'size'          => $row->file_size,
            ]);
        });

        Schema::table('media_library', function (Blueprint $table) {
            $table->dropIndex(['file_type']);
            $table->dropIndex(['uploaded_by']);
            $table->dropForeign(['uploaded_by']);
            $table->dropColumn(['uuid', 'original_filename', 'file_type', 'file_size', 'url', 'title', 'caption', 'description', 'metadata', 'uploaded_by']);
        });
    }
};
