@props(['initialCoupon' => null])

@if($initialCoupon)
    <div class="bg-green-50 border border-green-200 rounded-lg p-3 flex justify-between items-center animate-in fade-in">
        <div class="flex items-center gap-2 text-green-700">
            <i data-lucide="tag" width="18"></i>
            <span class="font-bold">{{ $initialCoupon['code'] }}</span>
            <span class="text-xs bg-green-200 px-2 py-0.5 rounded-full">-{{ $initialCoupon['discountPercentage'] }}%</span>
        </div>
        <form action="/cupom/remover" method="POST">
            @csrf
            <button type="submit" class="text-green-600 hover:text-green-800 p-1 hover:bg-green-100 rounded">
                <i data-lucide="x" width="16"></i>
            </button>
        </form>
    </div>
@else
    <form action="/cupom/aplicar" method="POST" class="flex gap-2">
        @csrf
        <div class="relative grow">
            <i data-lucide="tag" class="absolute left-3 top-3 text-gray-400" width="18"></i>
            <input 
                name="code"
                class="w-full border border-gray-300 rounded-lg pl-10 p-2.5 outline-none focus:ring-2 focus:ring-primary uppercase transition-all"
                placeholder="CÓDIGO" 
                required
            />
        </div>
        <button type="submit" class="bg-gray-900 text-white px-6 rounded-lg text-sm font-bold hover:bg-black transition-colors">
            Aplicar
        </button>
    </form>
@endif