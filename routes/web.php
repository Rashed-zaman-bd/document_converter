<?php

use App\Http\Controllers\DocumentConverterController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {

    $tools = [

        [
            'name' => 'PDF to Word',
            'slug' => 'pdf-to-word',
            'icon' => 'DOCX',
            'description' => 'Convert PDF documents into editable Word files.',
        ],

        [
            'name' => 'DOCX to PDF',
            'slug' => 'docx-to-pdf',
            'icon' => 'PDF',
            'description' => 'Convert Word documents into PDF files.',
        ],

        [
            'name' => 'PDF to JPG',
            'slug' => 'pdf-to-jpg',
            'icon' => 'JPG',
            'description' => 'Convert PDF pages into high-quality JPG images.',
        ],

        [
            'name' => 'JPG to PDF',
            'slug' => 'jpg-to-pdf',
            'icon' => 'JPG',
            'description' => 'Combine JPG images into a single PDF file.',
        ],

        [
            'name' => 'PDF to EPUB',
            'slug' => 'pdf-to-epub',
            'icon' => 'EPUB',
            'description' => 'Convert PDF documents into EPUB ebooks.',
        ],

        [
            'name' => 'EPUB to PDF',
            'slug' => 'epub-to-pdf',
            'icon' => 'PDF',
            'description' => 'Convert EPUB ebooks into PDF documents.',
        ],

        [
            'name' => 'HEIC to PDF',
            'slug' => 'heic-to-pdf',
            'icon' => 'HEIC',
            'description' => 'Convert HEIC images into PDF documents.',
        ],


    ];

    return view('home', compact('tools'));

})->name('home');


Route::get('/convert/{tool}', [
    DocumentConverterController::class,
    'show'
])->name('document.tool');

Route::post('/convert/{tool}', [
    DocumentConverterController::class,
    'convert'
])->name('document.convert');

Route::get('/conversion-result/{id}', [
    DocumentConverterController::class,
    'result'
])->name('document.result');

Route::get('/download/{id}', [
    DocumentConverterController::class,
    'download'
])->name('document.download');