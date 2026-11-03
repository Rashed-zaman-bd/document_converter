<?php

namespace App\Http\Controllers;

use App\Services\DocumentConversionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Throwable;

class DocumentConverterController extends Controller
{
    protected array $tools = [
        'pdf-to-jpg' => [
            'name' => 'PDF to JPG',
            'description' => 'Convert PDF pages into JPG images.',
            'accept' => '.pdf,application/pdf',
            'multiple' => false,
            'type' => 'pdf',
        ],

        'jpg-to-pdf' => [
            'name' => 'JPG to PDF',
            'description' => 'Convert multiple JPG images into one PDF.',
            'accept' => '.jpg,.jpeg,image/jpeg',
            'multiple' => true,
            'type' => 'image',
        ],

        'pdf-to-word' => [
            'name' => 'PDF to Word',
            'description' => 'Convert PDF documents into editable Word files.',
            'accept' => '.pdf,application/pdf',
            'multiple' => false,
            'type' => 'pdf',
        ],

        'docx-to-pdf' => [
            'name' => 'DOCX to PDF',
            'description' => 'Convert Word documents into PDF files.',
            'accept' => '.docx,application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'multiple' => false,
            'type' => 'docx',
        ],

        'pdf-to-epub' => [
            'name' => 'PDF to EPUB',
            'description' => 'Convert PDF documents into EPUB ebooks.',
            'accept' => '.pdf,application/pdf',
            'multiple' => false,
            'type' => 'pdf',
        ],

        'epub-to-pdf' => [
            'name' => 'EPUB to PDF',
            'description' => 'Convert EPUB ebooks into PDF documents.',
            'accept' => '.epub,application/epub+zip',
            'multiple' => false,
            'type' => 'epub',
        ],

        'heic-to-pdf' => [
            'name' => 'HEIC to PDF',
            'description' => 'Convert HEIC and HEIF images into PDF documents.',
            'accept' => '.heic,.heif,image/heic,image/heif',
            'multiple' => false,
            'type' => 'heic',
        ],
    ];

    public function show(string $tool)
    {
        abort_unless(isset($this->tools[$tool]), 404);

        return view('document.upload', [
            'tool' => $this->tools[$tool],
            'slug' => $tool,
        ]);
    }

    public function convert(
        Request $request,
        string $tool,
        DocumentConversionService $service
    ) {
        abort_unless(isset($this->tools[$tool]), 404);

        $config = $this->tools[$tool];

        $fileRules = match ($config['type']) {
            'pdf' => ['required', 'file', 'max:10240', 'mimes:pdf'],
            'docx' => ['required', 'file', 'max:10240', 'mimes:docx'],
            'image' => ['required', 'file', 'max:10240', 'mimes:jpg,jpeg'],
            'epub' => ['required', 'file', 'max:10240', 'mimes:epub'],
            'heic' => ['required', 'file', 'max:10240', 'mimes:heic,heif'],
            default => ['required', 'file', 'max:10240'],
        };

        $request->validate([
            'files' => [
                'required',
                'array',
                'min:1',
                'max:' . ($config['multiple'] ? 20 : 1),
            ],
            'files.*' => $fileRules,
        ]);

        $jobId = (string) Str::uuid();

        $jobDirectory = storage_path(
            'app/private/conversions/' . $jobId
        );

        File::ensureDirectoryExists($jobDirectory);

        try {
            $inputPaths = [];

            foreach ($request->file('files') as $file) {

                $extension = strtolower($file->getClientOriginalExtension());

                $filename = Str::uuid() . '.' . $extension;

                $file->move($jobDirectory, $filename);

                $filePath = $jobDirectory . DIRECTORY_SEPARATOR . $filename;

                if (!is_file($filePath) || !is_readable($filePath)) {
                    throw new \RuntimeException('Uploaded file could not be saved.');
                }

                $inputPaths[] = $filePath;

                logger()->info('Uploaded conversion file', [
                    'path' => $filePath,
                    'exists' => file_exists($filePath),
                    'readable' => is_readable($filePath),
                    'size' => filesize($filePath),
                ]);
            }

            $outputPath = match ($tool) {
                'pdf-to-jpg' => $service->pdfToJpg(
                    $inputPaths[0],
                    $jobDirectory
                ),

                'jpg-to-pdf' => $service->jpgToPdf(
                    $inputPaths,
                    $jobDirectory
                ),

                'pdf-to-word' => $service->pdfToWord(
                    $inputPaths[0],
                    $jobDirectory
                ),

                'docx-to-pdf' => $service->docxToPdf(
                    $inputPaths[0],
                    $jobDirectory
                ),

                'pdf-to-epub' => $service->pdfToEpub(
                    $inputPaths[0],
                    $jobDirectory
                ),

                'epub-to-pdf' => $service->epubToPdf(
                    $inputPaths[0],
                    $jobDirectory
                ),

                'heic-to-pdf' => $service->heicToPdf(
                    $inputPaths[0],
                    $jobDirectory
                ),

                default => throw new \RuntimeException(
                    'Unsupported conversion tool.'
                ),
            };

            if (!file_exists($outputPath)) {
                throw new \RuntimeException(
                    'The converted file could not be found.'
                );
            }

            $downloadName = match ($tool) {
                'pdf-to-jpg' => 'converted-images.zip',
                'jpg-to-pdf', 'docx-to-pdf' => 'converted.pdf',
                'pdf-to-word' => 'converted.docx',
                'pdf-to-epub' => 'converted.epub',
                'epub-to-pdf' => 'converted.pdf',
                'heic-to-pdf' => 'converted.pdf',
            };

            Cache::put(
                'document_conversion:' . $jobId,
                [
                    'path' => $outputPath,
                    'name' => $downloadName,
                    'directory' => $jobDirectory,
                ],
                now()->addHour()
            );

            return redirect()->route('document.result', $jobId);

        } catch (Throwable $exception) {
            File::deleteDirectory($jobDirectory);

            report($exception);

            return back()->withErrors([
                'conversion' => app()->isLocal()
                    ? $exception->getMessage()
                    : 'Conversion failed. Please try another file.',
            ]);
        }
    }

    public function result(string $id)
    {
        $result = Cache::get('document_conversion:' . $id);

        abort_unless(
            $result && file_exists($result['path']),
            404
        );

        return view('document.result', compact('result', 'id'));
    }

    public function download(string $id)
    {
        $result = Cache::get('document_conversion:' . $id);

        abort_unless(
            $result && file_exists($result['path']),
            404
        );

        Cache::forget('document_conversion:' . $id);

        return response()
            ->download($result['path'], $result['name'])
            ->deleteFileAfterSend(true);
    }
}
