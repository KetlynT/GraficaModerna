@extends('layouts.app')

@section('content')
<div class="max-w-4xl mx-auto px-4 py-16 min-h-screen animate-in fade-in">
    <h1 class="text-4xl font-bold text-gray-900 mb-8 border-b pb-4">{{ $page->title }}</h1>
    
    <div class="prose prose-lg prose-blue text-gray-600 max-w-none">
        {!! $page->content !!}
    </div>
</div>
@endsection