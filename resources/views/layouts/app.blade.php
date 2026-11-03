
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>@yield('title', 'Document Converter')</title>

    <meta name="description"
          content="Convert PDF, Word, EPUB and image files online.">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen bg-gray-50 text-gray-800">

    <header class="fixed top-0 left-0 z-50 w-full border-b border-gray-200 bg-white shadow-lg">
        <div class="mx-auto flex max-w-7xl items-center justify-between px-5 py-4">

            <a href="{{ route('home') }}"
            class="text-2xl font-extrabold tracking-tight">
                <span class="text-amber-600">Document</span>
                <span class="text-gray-900">Converter</span>
            </a>

            <nav class="hidden gap-8 text-sm font-medium md:flex">
                <a href="{{ route('home') }}"
                class="transition hover:text-amber-600">Home</a>

                <a href="#tools"
                class="transition hover:text-amber-600">All Tools</a>

                <a href="#about"
                class="transition hover:text-amber-600">About</a>
            </nav>

        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <footer class="mt-16 border-t bg-white">
        <div class="mx-auto max-w-7xl px-5 py-8 text-center text-sm text-gray-500">
            Copyright &copy; {{ date('Y') }} Document Converter.
            All rights reserved.
        </div>
    </footer>

</body>
</html>
