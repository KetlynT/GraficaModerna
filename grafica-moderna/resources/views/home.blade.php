@extends('layouts.app')

@section('content')
    <div class="bg-blue-600 text-white py-20 text-center">
        <h1 class="text-5xl font-bold">Impressão de Qualidade</h1>
        <p class="mt-4 text-xl">Dê vida às suas ideias com a Gráfica Moderna</p>
    </div>

    <div class="max-w-7xl mx-auto px-4 py-12">
        <h2 class="text-3xl font-bold mb-8">Destaques</h2>
        <div class="grid grid-cols-1 md:grid-cols-4 gap-6">
            @foreach($products as $product)
                <div class="bg-white rounded-lg shadow hover:shadow-lg transition">
                    <img src="{{ $product->image_url }}" alt="{{ $product->name }}" class="h-48 w-full object-cover rounded-t-lg">
                    <div class="p-4">
                        <h3 class="text-lg font-bold">{{ $product->name }}</h3>
                        <p class="text-blue-600 font-bold mt-2">R$ {{ number_format($product->price, 2, ',', '.') }}</p>
                        <a href="#" class="block mt-4 text-center bg-blue-600 text-white py-2 rounded hover:bg-blue-700">
                            Ver Detalhes
                        </a>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
@endsection