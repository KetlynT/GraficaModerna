@extends('layouts.admin')

@section('title', 'Editar Página: ' . $page->title)

@section('content')
<form action="/admin/paginas/{{ $page->id }}" method="POST" class="max-w-4xl mx-auto">
    @csrf
    @method('PUT')

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-6">
        
        <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">Título da Página</label>
            <input type="text" name="title" value="{{ old('title', $page->title) }}" class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 outline-none" required>
        </div>

        <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">Slug (URL Amigável)</label>
            <input type="text" name="slug" value="{{ old('slug', $page->slug) }}" class="w-full bg-gray-50 border border-gray-300 rounded-lg p-3 text-gray-500" readonly>
        </div>

        <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">Conteúdo (HTML Permitido)</label>
            <textarea name="content" rows="15" class="w-full border border-gray-300 rounded-lg p-3 font-mono text-sm focus:ring-2 focus:ring-blue-500 outline-none">{{ old('content', $page->content) }}</textarea>
            <p class="text-xs text-gray-400 mt-2">Dica: Use tags HTML como &lt;h2&gt;, &lt;p&gt;, &lt;ul&gt; para formatar o texto.</p>
        </div>

        <div class="flex items-center justify-end gap-4 pt-4 border-t border-gray-100">
            <a href="/admin/paginas" class="text-gray-600 hover:text-gray-800 font-medium">Cancelar</a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-bold shadow-lg flex items-center gap-2">
                <i data-lucide="save" width="18"></i> Salvar Alterações
            </button>
        </div>
    </div>
</form>
@endsection