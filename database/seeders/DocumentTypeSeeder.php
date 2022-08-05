<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\DocumentType;

class DocumentTypeSeeder extends Seeder
{
    public function run()
    {
        DocumentType::create([
            'name' => 'xls',
            'description' => 'Электронная таблица (Spreadsheet)',
        ]);

        DocumentType::create([
            'name' => 'xlsx',
            'description' => 'Электронная таблица (Spreadsheet)',
        ]);

        DocumentType::create([
            'name' => 'doc',
            'description' => 'Документ (Document)',
        ]);

        DocumentType::create([
            'name' => 'docx',
            'description' => 'Документ (Document)',
        ]);

        DocumentType::create([
            'name' => 'txt',
            'description' => 'Текстовый документ (Text document)',
        ]);

        DocumentType::create([
            'name' => 'png',
            'description' => 'Изображение (Image)',
        ]);

        DocumentType::create([
            'name' => 'jpg',
            'description' => 'Изображение (Image)',
        ]);
    }
}
