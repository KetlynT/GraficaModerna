@extends('layouts.admin')

@section('title', 'Páginas do Site')

@section('content')
<div class="bg-white rounded-xl shadow-sm border border-gray-100 overflow-hidden">
    <div class="p-6 border-b border-gray-100 bg-gray-50">
        <h3 class="font-bold text-gray-700">Páginas Institucionais</h3>
        <p class="text-xs text-gray-500">Edite o conteúdo de "Sobre Nós", "Política de Privacidade", etc.</p>
    </div>

    <table class="w-full text-left text-sm">
        <thead class="bg-gray-100 text-gray-600 uppercase text-xs font-bold tracking-wider">
            <tr>
                <th class="px-6 py-4">Título</th>
                <th class="px-6 py-4">Slug (URL)</th>
                <th class="px-6 py-4">Última Atualização</th>
                <th class="px-6 py-4 text-center">Ação</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100">
            @foreach($pages as $page)
                <tr class="hover:bg-gray-50">
                    <td class="px-6 py-4 font-bold text-gray-800">{{ $page->title }}</td>
                    <td class="px-6 py-4 text-blue-600 text-xs font-mono">/pagina/{{ $page->slug }}</td>
                    <td class="px-6 py-4 text-gray-500">{{ $page->updated_at->format('d/m/Y H:i') }}</td>
                    <td class="px-6 py-4 text-center">
                        <a href="/admin/paginas/{{ $page->id }}/editar" class="bg-white border border-gray-300 hover:bg-gray-50 text-gray-700 px-3 py-1 rounded font-bold text-xs inline-flex items-center gap-1">
                            <i data-lucide="edit" width="12"></i> Editar
                        </a>
                    </td>
                </tr>
            @endforeach
        </tbody>
    </table>
</div>
@endsection