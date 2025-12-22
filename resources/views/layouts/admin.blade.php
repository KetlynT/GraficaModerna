<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Administrativo - {{ $settings->site_name ?? 'Gráfica' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://unpkg.com/lucide@latest"></script>
</head>
<body class="bg-gray-100 min-h-screen flex">

    <aside class="w-64 bg-gray-900 text-white flex flex-col fixed h-full z-20 transition-all">
        <div class="h-16 flex items-center px-6 border-b border-gray-800 font-bold text-xl tracking-wider">
            ADMIN
        </div>

        <nav class="flex-1 py-6 space-y-1 px-3">
            <a href="/admin" class="flex items-center gap-3 px-3 py-2.5 rounded hover:bg-gray-800 {{ request()->is('admin') ? 'bg-blue-600 text-white' : 'text-gray-400' }}">
                <i data-lucide="layout-dashboard" width="20"></i> Dashboard
            </a>
            <a href="/admin/pedidos" class="flex items-center gap-3 px-3 py-2.5 rounded hover:bg-gray-800 {{ request()->is('admin/pedidos*') ? 'bg-blue-600 text-white' : 'text-gray-400' }}">
                <i data-lucide="shopping-bag" width="20"></i> Pedidos
            </a>
            <a href="/admin/produtos" class="flex items-center gap-3 px-3 py-2.5 rounded hover:bg-gray-800 {{ request()->is('admin/produtos*') ? 'bg-blue-600 text-white' : 'text-gray-400' }}">
                <i data-lucide="package" width="20"></i> Produtos
            </a>
            <a href="/admin/configuracoes" class="flex items-center gap-3 px-3 py-2.5 rounded hover:bg-gray-800 {{ request()->is('admin/configuracoes*') ? 'bg-blue-600 text-white' : 'text-gray-400' }}">
                <i data-lucide="settings" width="20"></i> Configurações
            </a>
        </nav>

        <div class="p-4 border-t border-gray-800">
            <a href="/" class="flex items-center gap-2 text-sm text-gray-400 hover:text-white mb-4">
                <i data-lucide="external-link" width="16"></i> Ver Loja
            </a>
            <form action="/logout" method="POST">
                @csrf
                <button type="submit" class="flex items-center gap-2 text-sm text-red-400 hover:text-red-300 w-full">
                    <i data-lucide="log-out" width="16"></i> Sair
                </button>
            </form>
        </div>
    </aside>

    <main class="ml-64 flex-1 flex flex-col min-h-screen">
        <header class="h-16 bg-white border-b border-gray-200 flex items-center justify-between px-8 sticky top-0 z-10">
            <h2 class="text-lg font-bold text-gray-800">
                @yield('title', 'Dashboard')
            </h2>
            <div class="flex items-center gap-4">
                <div class="text-right hidden sm:block">
                    <p class="text-sm font-bold text-gray-900">{{ auth()->user()->name }}</p>
                    <p class="text-xs text-gray-500">Administrador</p>
                </div>
                <div class="w-10 h-10 bg-blue-100 rounded-full flex items-center justify-center text-blue-600 font-bold">
                    {{ substr(auth()->user()->name, 0, 1) }}
                </div>
            </div>
        </header>

        <div class="p-8">
            @yield('content')
        </div>
    </main>

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            lucide.createIcons();
        });
    </script>
    @stack('scripts')
</body>
</html>