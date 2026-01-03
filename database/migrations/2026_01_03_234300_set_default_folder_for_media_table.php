<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use App\Models\Folder;
use App\Models\Media;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // Создаем папку "Общая" если её нет
        $generalFolder = Folder::where('name', 'Общая')->whereNull('parent_id')->first();
        
        if (!$generalFolder) {
            $generalFolder = Folder::create([
                'name' => 'Общая',
                'slug' => 'obshchaya',
                'src' => '/media/photos/obshchaya',
                'parent_id' => null,
                'position' => 0,
                'protected' => false,
                'is_trash' => false,
            ]);
        }
        
        // Обновляем существующие записи media, у которых folder_id = null
        Media::whereNull('folder_id')->update(['folder_id' => $generalFolder->id]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // При откате миграции можно установить folder_id в null для записей из папки "Общая"
        // Но обычно откат миграций не требуется для данных
    }
};
