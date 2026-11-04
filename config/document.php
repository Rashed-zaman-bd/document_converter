
<?php

return [

    'libreoffice_path' => env(
        'LIBREOFFICE_PATH',
        PHP_OS_FAMILY === 'Windows'
            ? 'C:/Program Files/LibreOffice/program/soffice.com'
            : '/usr/bin/soffice'
    ),

    'calibre_path' => env(
        'CALIBRE_PATH',
        PHP_OS_FAMILY === 'Windows'
            ? 'C:/Program Files/Calibre2/ebook-convert.exe'
            : '/usr/bin/ebook-convert'
    ),

    'imagemagick_path' => env(
        'IMAGEMAGICK_PATH',
        PHP_OS_FAMILY === 'Windows'
            ? 'C:/Program Files/ImageMagick-7.1.2-Q16-HDRI/magick.exe'
            : '/usr/bin/magick'
    ),

    'pdfinfo_path' => env('PDFINFO_PATH', 'pdfinfo'),

    'pdftoppm_path' => env('PDFTOPPM_PATH', 'pdftoppm'),

    'python_path' => env(
        'PYTHON_PATH',
        PHP_OS_FAMILY === 'Windows' ? 'python' : 'python3'
    ),

];
