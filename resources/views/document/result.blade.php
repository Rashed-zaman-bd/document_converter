
@extends('layouts.app')

@section('title', 'Conversion Complete')

@section('content')

<section class="mx-auto max-w-xl px-5 py-20 text-center">

    <div class="mx-auto flex h-20 w-20 items-center justify-center rounded-full bg-green-100 text-4xl text-green-600">
        &#10003;
    </div>

    <h1 class="mt-6 text-3xl font-bold text-gray-900">
        Conversion Successful!
    </h1>

    <p class="mt-3 text-gray-500">
        Your converted file is ready to download.
    </p>

    <a
        href="{{ route('document.download', $id) }}"
        class="mt-8 inline-flex items-center justify-center rounded-lg bg-blue-600 px-8 py-3 font-semibold text-white transition hover:bg-blue-700"
    >
        Download {{ $result['name'] }}
    </a>

    <div class="mt-6">
        <a href="{{ route('home') }}"
           class="text-sm font-medium text-blue-600 hover:underline">
            Convert Another File
        </a>
    </div>

</section>

@endsection
