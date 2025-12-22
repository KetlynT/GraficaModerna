@extends('layouts.admin')

@section('title', 'Templates de E-mail')

@section('content')
<div class="grid gap-6 md:grid-cols-2 lg:grid-cols-3">
    @foreach($templates as $template)
        <div class="bg-white rounded-xl shadow-sm border border-gray-100 hover:shadow-md transition-shadow p-6 flex flex-col">
            <div class="flex items-start justify-between mb-4">
                <div class="p-3 bg-blue-50 text-blue-600 rounded-lg">
                    <i data-lucide="mail" width="24"></i>
                </div>
                <span class="text-xs font-mono bg-gray-100 text-gray-600 px-2 py-1 rounded">{{ $template->type }}</span>
            </div>
            
            <h3 class="font-bold text-lg text-gray-800 mb-2">{{ $template->subject }}</h3>
            <p class="text-sm text-gray-500 mb-6 flex-1 line-clamp-3">
                {{ strip_tags($template->body) }}
            </p>

            <a href="/admin/email-templates/{{ $template->id }}/editar" class="w-full bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 font-bold py-2 rounded-lg text-center transition-colors">
                Editar Modelo
            </a>
        </div>
    @endforeach
</div>
@endsection