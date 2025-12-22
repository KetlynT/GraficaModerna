<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>{{ $settings->site_name ?? 'Gráfica Moderna' }} - Sua gráfica online</title>
    <meta name="description" content="Sua gráfica online de confiança">

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <style>
        :root {
            --color-primary: #2563eb;
            --color-secondary: #1e40af;
            --color-footer-bg: #111827;
            --color-footer-text: #d1d5db;
        }
    </style>
</head>
<body class="bg-gray-50 min-h-screen flex flex-col">

    @include('partials.header')

    <main class="grow">
        @yield('content')
    </main>

    @include('partials.footer')
    
    @include('components.whatsapp-button')
    @include('components.cookie-consent')

    <script>
        document.addEventListener("DOMContentLoaded", function() {
            lucide.createIcons();
        });
    </script>
    @stack('scripts')
</body>
</html>