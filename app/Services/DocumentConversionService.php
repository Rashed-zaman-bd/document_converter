<?php

namespace App\Services;

use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Process;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use RuntimeException;
use ZipArchive;
use Symfony\Component\Process\Process as SymfonyProcess;

class DocumentConversionService
{
    /**
     * Convert PDF pages into JPG images and ZIP them.
     */
    
public function pdfToJpg(
    string $pdfPath,
    string $jobDirectory
): string {

    if (!is_file($pdfPath) || !is_readable($pdfPath)) {
        throw new RuntimeException('PDF file not found or unreadable.');
    }

    // Step 1: Get PDF information.
    $pdfInfo = Process::timeout(30)->run([
        'pdfinfo',
        $pdfPath,
    ]);

    if ($pdfInfo->failed()) {
        throw new RuntimeException(
            'Unable to read PDF information: ' . $pdfInfo->errorOutput()
        );
    }

    preg_match(
        '/^Pages:\s+(\d+)/mi',
        $pdfInfo->output(),
        $matches
    );

    $pages = (int) ($matches[1] ?? 0);

    if ($pages < 1 || $pages > 100) {
        throw new RuntimeException(
            'PDF must contain between 1 and 100 pages.'
        );
    }

    // Step 2: Create image directory.
    $imageDirectory = $jobDirectory . DIRECTORY_SEPARATOR . 'images';

    File::ensureDirectoryExists($imageDirectory);

    $prefix = $imageDirectory . DIRECTORY_SEPARATOR . 'page';

    // Step 3: Convert PDF pages into JPG.
    $result = Process::timeout(180)->run([
        'pdftoppm',
        '-jpeg',
        '-r',
        '150',
        $pdfPath,
        $prefix,
    ]);

    if ($result->failed()) {
        throw new RuntimeException(
            'PDF to JPG conversion failed: ' . $result->errorOutput()
        );
    }

    // Step 4: Find generated JPG files.
    // IMPORTANT: Use page-*.jpg, without a backslash.
    $images = glob($imageDirectory . DIRECTORY_SEPARATOR . 'page-*.jpg');

    if (empty($images)) {
        throw new RuntimeException(
            'No JPG images were generated.'
        );
    }

    // Sort images by page number.
    natsort($images);
    $images = array_values($images);

    // Check that all expected pages were generated.
    if (count($images) !== $pages) {
        throw new RuntimeException(
            'Expected ' . $pages . ' pages but generated ' . count($images) . ' JPG images.'
        );
    }

    // Step 5: Create ZIP archive.
    $zipPath = $jobDirectory . DIRECTORY_SEPARATOR . 'converted-images.zip';

    if (file_exists($zipPath)) {
        File::delete($zipPath);
    }

    $zip = new ZipArchive();

    $openResult = $zip->open(
        $zipPath,
        ZipArchive::CREATE | ZipArchive::OVERWRITE
    );

    if ($openResult !== true) {
        throw new RuntimeException(
            'Unable to create ZIP archive. Error code: ' . $openResult
        );
    }

    // Step 6: Add every JPG into ZIP.
    foreach ($images as $index => $image) {

        if (!is_file($image) || filesize($image) === 0) {
            $zip->close();

            throw new RuntimeException(
                'Invalid JPG image: ' . basename($image)
            );
        }

        $entryName = 'page-' . ($index + 1) . '.jpg';

        if (!$zip->addFile($image, $entryName)) {
            $zip->close();

            throw new RuntimeException(
                'Unable to add image to ZIP: ' . basename($image)
            );
        }
    }

    // Step 7: Finalize ZIP archive.
    if (!$zip->close()) {
        throw new RuntimeException(
            'Unable to finalize ZIP archive.'
        );
    }

    // Step 8: Verify ZIP file.
    clearstatcache(true, $zipPath);

    if (!is_file($zipPath) || filesize($zipPath) === 0) {
        throw new RuntimeException(
            'Generated ZIP file is missing or empty.'
        );
    }

    // Check ZIP signature.
    $handle = fopen($zipPath, 'rb');

    if ($handle === false) {
        throw new RuntimeException('Unable to read generated ZIP file.');
    }

    $signature = fread($handle, 4);
    fclose($handle);

    if (!in_array($signature, ["PK\x03\x04", "PK\x05\x06", "PK\x07\x08"], true)) {
        throw new RuntimeException('Generated file is not a valid ZIP archive.');
    }

    // Reopen archive and verify its contents.
    $checkZip = new ZipArchive();

    if ($checkZip->open($zipPath, ZipArchive::CHECKCONS) !== true) {
        throw new RuntimeException(
            'Generated ZIP archive failed integrity verification.'
        );
    }

    if ($checkZip->numFiles !== count($images)) {
        $checkZip->close();

        throw new RuntimeException(
            'ZIP archive does not contain all converted images.'
        );
    }

    $checkZip->close();

    logger()->info('PDF to JPG ZIP created successfully', [
        'pdf' => basename($pdfPath),
        'pages' => $pages,
        'images' => count($images),
        'zip' => $zipPath,
        'zip_size' => filesize($zipPath),
    ]);

    return $zipPath;
}



    /**
     * Convert multiple JPG images into one PDF.
     */
    public function jpgToPdf(
        array $imagePaths,
        string $jobDirectory
    ): string {

        if (empty($imagePaths)) {
            throw new RuntimeException('No images provided.');
        }

        $html = '
            <!DOCTYPE html>
            <html>
            <head>
                <style>
                    @page {
                        margin: 10mm;
                    }

                    body {
                        margin: 0;
                        padding: 0;
                    }

                    .image-page {
                        width: 100%;
                        height: 275mm;
                        text-align: center;
                        page-break-after: always;
                    }

                    .image-page:last-child {
                        page-break-after: auto;
                    }

                    img {
                        max-width: 100%;
                        max-height: 270mm;
                        object-fit: contain;
                    }
                </style>
            </head>
            <body>
        ';

        foreach ($imagePaths as $imagePath) {

            if (!is_file($imagePath)) {
                throw new RuntimeException('Image file not found.');
            }

            $imageData = base64_encode(
                file_get_contents($imagePath)
            );

            $html .= '
                <div class="image-page">
                    <img src="data:image/jpeg;base64,' .
                        $imageData .
                    '">
                </div>
            ';
        }

        $html .= '</body></html>';

        $pdf = Pdf::loadHTML($html)
            ->setPaper('a4', 'portrait');

        $outputPath = $jobDirectory . DIRECTORY_SEPARATOR . 'converted.pdf';

        file_put_contents($outputPath, $pdf->output());

        if (!is_file($outputPath) || filesize($outputPath) === 0) {
            throw new RuntimeException('JPG to PDF conversion failed.');
        }

        return $outputPath;
    }


    /**
     * Convert PDF to editable Word document.
     */
    public function pdfToWord(
        string $pdfPath,
        string $jobDirectory
    ): string {

        if (!is_file($pdfPath)) {
            throw new RuntimeException('PDF file not found.');
        }

        $outputPath = $jobDirectory . DIRECTORY_SEPARATOR . 'converted.docx';

        $scriptPath = base_path('scripts/pdf_to_word.py');

        if (!is_file($scriptPath)) {
            throw new RuntimeException('PDF conversion script not found.');
        }

        $result = Process::timeout(300)->run([
            'python',
            $scriptPath,
            $pdfPath,
            $outputPath,
        ]);

        if ($result->failed() || !is_file($outputPath)) {
            throw new RuntimeException(
                'PDF to Word conversion failed: ' .
                $result->errorOutput() .
                ' ' .
                $result->output()
            );
        }

        if (filesize($outputPath) === 0) {
            throw new RuntimeException('Generated Word document is empty.');
        }

        return $outputPath;
    }


    /**
     * Convert Word document to PDF using LibreOffice.
     */
    
    public function docxToPdf(
        string $docxPath,
        string $jobDirectory
    ): string {

        $soffice = config('document.libreoffice_path');

        // Resolve and validate the uploaded DOCX.
        $docxPath = realpath($docxPath);

        if (
            !$docxPath ||
            !is_file($docxPath) ||
            !is_readable($docxPath) ||
            filesize($docxPath) === 0
        ) {
            throw new RuntimeException(
                'DOCX file is missing, empty, or unreadable.'
            );
        }

        if (!is_file($soffice)) {
            throw new RuntimeException(
                'LibreOffice executable not found: ' . $soffice
            );
        }

        // Verify DOCX archive structure.
        $zip = new ZipArchive();

        if ($zip->open($docxPath) !== true) {
            throw new RuntimeException(
                'Uploaded file is not a valid DOCX archive.'
            );
        }

        $hasDocument = $zip->locateName('word/document.xml') !== false;
        $hasContentTypes = $zip->locateName('[Content_Types].xml') !== false;

        $zip->close();

        if (!$hasDocument || !$hasContentTypes) {
            throw new RuntimeException(
                'Invalid DOCX document structure.'
            );
        }

        // Create output directory.
        $outputDirectory = $jobDirectory .
            DIRECTORY_SEPARATOR . 'output';

        File::ensureDirectoryExists($outputDirectory);

        // Create a unique LibreOffice profile.
        $profilePath = storage_path(
            'app/lo-profiles/' . Str::uuid()
        );

        File::ensureDirectoryExists($profilePath);

        $profileUri = 'file:///' . str_replace(
            '\\',
            '/',
            $profilePath
        );

        // Run LibreOffice.
        $process = new SymfonyProcess(
            [
                $soffice,
                '--headless',
                '--nologo',
                '--nodefault',
                '--nolockcheck',
                '--nofirststartwizard',
                '-env:UserInstallation=' . $profileUri,
                '--convert-to',
                'pdf:writer_pdf_Export',
                '--outdir',
                $outputDirectory,
                $docxPath,
            ],
            $jobDirectory,
            [
                'SAL_DISABLE_OPENCL' => '1',
            ]
        );

        $process->setTimeout(180);
        $process->run();

        logger()->info('LibreOffice conversion result', [
            'input' => $docxPath,
            'input_exists' => file_exists($docxPath),
            'output_directory' => $outputDirectory,
            'successful' => $process->isSuccessful(),
            'output' => $process->getOutput(),
            'error' => $process->getErrorOutput(),
        ]);

        if (!$process->isSuccessful()) {
            throw new RuntimeException(
                'LibreOffice failed: ' .
                $process->getErrorOutput() .
                ' ' .
                $process->getOutput()
            );
        }

        $generatedPdf = $outputDirectory .
            DIRECTORY_SEPARATOR .
            pathinfo($docxPath, PATHINFO_FILENAME) .
            '.pdf';

        if (
            !is_file($generatedPdf) ||
            filesize($generatedPdf) === 0
        ) {
            throw new RuntimeException(
                'LibreOffice did not generate a valid PDF.'
            );
        }

        return $generatedPdf;
    }


    public function pdfToEpub(
        string $pdfPath,
        string $jobDirectory
    ): string {
        $converter = config('document.calibre_path');

        if (!file_exists($converter)) {
            throw new RuntimeException(
                'Calibre ebook-convert.exe was not found.'
            );
        }

        if (!is_file($pdfPath) || !is_readable($pdfPath)) {
            throw new RuntimeException(
                'PDF input file not found or unreadable.'
            );
        }

        $tempDirectory = storage_path('app/calibre-temp');

        File::ensureDirectoryExists($tempDirectory);

        $outputPath = $jobDirectory . DIRECTORY_SEPARATOR . 'converted.epub';

        $process = new SymfonyProcess([
            $converter,
            $pdfPath,
            $outputPath,
        ]);

        $process->setEnv([
            'TEMP' => $tempDirectory,
            'TMP' => $tempDirectory,
            'TMPDIR' => $tempDirectory,
        ]);

        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful() || !is_file($outputPath)) {
            throw new RuntimeException(
                'PDF to EPUB conversion failed: ' .
                ($process->getErrorOutput() ?: $process->getOutput())
            );
        }

        return $outputPath;
    }

    public function epubToPdf(
        string $epubPath,
        string $jobDirectory
    ): string {
        $converter = config('document.calibre_path');

        if (!file_exists($converter)) {
            throw new RuntimeException(
                'Calibre ebook-convert.exe was not found.'
            );
        }

        if (!is_file($epubPath) || !is_readable($epubPath)) {
            throw new RuntimeException(
                'EPUB input file not found or unreadable.'
            );
        }

        $tempDirectory = storage_path('app/calibre-temp');

        File::ensureDirectoryExists($tempDirectory);

        $outputPath = $jobDirectory . DIRECTORY_SEPARATOR . 'converted.pdf';

        $process = new SymfonyProcess([
            $converter,
            $epubPath,
            $outputPath,
        ]);

        $process->setEnv([
            'TEMP' => $tempDirectory,
            'TMP' => $tempDirectory,
            'TMPDIR' => $tempDirectory,
        ]);

        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful() || !is_file($outputPath)) {
            throw new RuntimeException(
                'EPUB to PDF conversion failed: ' .
                ($process->getErrorOutput() ?: $process->getOutput())
            );
        }

        return $outputPath;
    }


    public function heicToPdf(string $inputPath, string $jobDirectory): string
    {
        $outputPath = $jobDirectory . DIRECTORY_SEPARATOR . 'converted.pdf';

        File::ensureDirectoryExists($jobDirectory);

        // ImageMagick executable
        $magickPath = config('document.imagemagick_path');

        if (!File::exists($magickPath)) {
            throw new RuntimeException('ImageMagick executable was not found.');
        }

        $process = new SymfonyProcess([
            $magickPath,
            $inputPath,
            $outputPath,
        ]);

        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful() || !File::exists($outputPath)) {
            throw new RuntimeException(
                'HEIC to PDF conversion failed: ' . $process->getErrorOutput()
            );
        }

        return $outputPath;
    }

}
