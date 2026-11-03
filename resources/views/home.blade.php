
@extends('layouts.app')

@section('title', 'Document Converter - Free Online Tools')

@section('content')

    {{-- Hero Section --}}
    <section class="pt-10 bg-gradient-to-br from-amber-100 via-white to-amber-200">
        <div class="mx-auto max-w-5xl px-5 py-20 text-center">

            <span class="inline-block rounded-full bg-blue-100 px-4 py-2 text-sm font-semibold text-amber-600">
                Simple & Fast Document Conversion
            </span>

            <h1 class="mt-6 text-4xl font-extrabold tracking-tight text-gray-900 md:text-6xl">
                Convert Your Files
                <span class="text-amber-600">Online</span>
            </h1>

            <p class="mx-auto mt-5 max-w-2xl text-lg leading-8 text-gray-600">
                Convert PDF, Word, EPUB and image files easily.
                Fast, simple and secure document conversion.
            </p>

            <a href="#tools"
               class="mt-8 inline-block rounded-lg bg-amber-600 px-8 py-3 font-semibold text-white shadow-lg transition hover:bg-amber-700">
                Explore All Tools
            </a>

        </div>
    </section>


    {{-- Conversion Tools --}}
    <section id="tools" class="mx-auto max-w-7xl px-5 py-30">

        <div class="mb-10 text-center">

            <h2 class="text-3xl font-bold text-gray-900">
                All Document Conversion Tools
            </h2>

            <p class="mt-3 text-gray-500">
                Select a tool to convert your documents.
            </p>

        </div>


        <div class="grid grid-cols-1 gap-5 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">

            @foreach ($tools as $tool)

                <a href="{{ route('document.tool', $tool['slug']) }}"
                    class="group block rounded-xl border border-gray-200 bg-white p-6 transition duration-300 hover:-translate-y-1 hover:border-amber-400 hover:shadow-xl">

                    <div class="flex h-14 w-14 items-center justify-center rounded-xl bg-amber-50 text-lg font-extrabold text-amber-600 group-hover:bg-amber-600 group-hover:text-white">
                        {{ $tool['icon'] }}
                    </div>

                    <h3 class="mt-5 text-lg font-bold text-gray-900">
                        {{ $tool['name'] }}
                    </h3>

                    <p class="mt-2 min-h-12 text-sm leading-6 text-gray-500">
                        {{ $tool['description'] }}
                    </p>

                    <div class="mt-5 flex items-center justify-between">

                        <span class="text-sm font-medium text-amber-600">
                            Convert Now
                        </span>

                        <span class="text-xl text-amber-600 transition group-hover:translate-x-1">
                            &rarr;
                        </span>

                    </div>

                </a>

            @endforeach

        </div>

    </section>


    {{-- About Section --}}
    <section id="about" class="bg-white py-16">

        <div class="mx-auto max-w-4xl px-5 text-center">

            <h2 class="text-3xl font-bold text-gray-900">
                Why Choose Document Converter?
            </h2>

            <p class="mt-5 leading-8 text-gray-600">
                Our document converter helps you convert different
                file formats easily. Upload your document, select
                the conversion tool and download your converted file.
            </p>

        </div>

    </section>

@endsection
