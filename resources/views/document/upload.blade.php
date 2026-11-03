
@extends('layouts.app')

@section('title', $tool['name'] . ' - Document Converter')

@section('content')

<section class="mx-auto max-w-3xl px-5 py-30">

    <div class="mb-8 text-center">

        <a href="{{ route('home') }}"
           class="text-sm text-amber-600 hover:underline">
            &larr; Back to Home
        </a>

        <h1 class="mt-5 text-3xl font-extrabold text-gray-900 md:text-4xl">
            {{ $tool['name'] }}
        </h1>

        <p class="mt-3 text-gray-500">
            {{ $tool['description'] }}
        </p>

    </div>


    @if ($errors->any())

        <div class="mb-6 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700">

            @foreach ($errors->all() as $error)
                <p>{{ $error }}</p>
            @endforeach

        </div>

    @endif


    <form
        action="{{ route('document.convert', $slug) }}"
        method="POST"
        enctype="multipart/form-data"
        class="rounded-2xl border border-gray-200 bg-white p-6 shadow-sm md:p-10"
    >

        @csrf

        <label
            for="files"
            class="flex min-h-64 cursor-pointer flex-col items-center justify-center rounded-xl border-2 border-dashed border-amber-300 bg-amber-50/50 p-6 text-center transition hover:border-amber-500 hover:bg-amber-50"
        >

            <div class="mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-amber-100 text-3xl text-amber-600">
                &#8593;
            </div>

            <h2 class="text-xl font-bold text-gray-800">
                Select Your Files
            </h2>

            <p class="mt-2 text-sm text-gray-500">
                Click here to upload your documents
            </p>

            <span class="mt-4 rounded-lg bg-amber-600 px-5 py-2 text-sm font-semibold text-white">
                Browse Files
            </span>

            <input
                type="file"
                id="files"
                name="files[]"
                accept="{{ $tool['accept'] }}"
                @if ($tool['multiple']) multiple @endif
                required
                class="hidden"
            >

        </label>


        <div id="file-list"
             class="mt-5 space-y-2 text-sm text-gray-600">
        </div>


        <p class="mt-5 text-center text-xs text-gray-500">
            Maximum file size: 10 MB per file.
            @if ($tool['multiple'])
                Maximum 20 images.
            @else
                Upload one PDF file.
            @endif
        </p>


        <button
            type="submit"
            class="mt-7 w-full rounded-lg bg-amber-600 px-6 py-3 font-bold text-white transition hover:bg-amber-700 cursor-pointer"
        >
            Convert Now
        </button>

    </form>

</section>


<script>
    const fileInput = document.getElementById('files');
    const fileList = document.getElementById('file-list');

    fileInput.addEventListener('change', function () {

        fileList.innerHTML = '';

        Array.from(this.files).forEach(file => {

            const item = document.createElement('div');

            item.className =
                'rounded-lg border border-gray-200 bg-gray-50 p-3';

            item.textContent =
                file.name + ' (' +
                (file.size / 1024 / 1024).toFixed(2) +
                ' MB)';

            fileList.appendChild(item);
        });

    });
</script>

@endsection
