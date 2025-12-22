@extends('layouts.admin')

@section('title', 'Editar Template de E-mail')

@section('content')
<form action="/admin/email-templates/{{ $template->id }}" method="POST" class="max-w-4xl mx-auto">
    @csrf
    @method('PUT')

    <div class="bg-white rounded-xl shadow-sm border border-gray-100 p-6 space-y-6">
        
        <div class="flex items-center gap-2 mb-4 p-4 bg-yellow-50 text-yellow-800 rounded-lg text-sm border border-yellow-100">
            <i data-lucide="info" width="18"></i>
            <p>Variáveis disponíveis: <strong>{name}</strong>, <strong>{order_id}</strong>, <strong>{total}</strong>, <strong>{link}</strong>. Não altere as chaves entre chaves.</p>
        </div>

        <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">Assunto do E-mail</label>
            <input type="text" name="subject" value="{{ old('subject', $template->subject) }}" class="w-full border border-gray-300 rounded-lg p-3 focus:ring-2 focus:ring-blue-500 outline-none" required>
        </div>

        <div>
            <label class="block text-sm font-bold text-gray-700 mb-1">Corpo do E-mail (HTML)</label>
            <textarea name="body" rows="15" class="w-full border border-gray-300 rounded-lg p-3 font-mono text-sm focus:ring-2 focus:ring-blue-500 outline-none">{{ old('body', $template->body) }}</textarea>
        </div>

        <div class="flex items-center justify-end gap-4 pt-4 border-t border-gray-100">
            <a href="/admin/email-templates" class="text-gray-600 hover:text-gray-800 font-medium">Cancelar</a>
            <button type="submit" class="bg-blue-600 hover:bg-blue-700 text-white px-6 py-3 rounded-lg font-bold shadow-lg flex items-center gap-2">
                <i data-lucide="save" width="18"></i> Salvar Template
            </button>
        </div>
    </div>
</form>
@endsection