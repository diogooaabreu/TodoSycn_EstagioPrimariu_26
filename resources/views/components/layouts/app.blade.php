<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>{{ $title ?? 'Todo Sync' }}</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        .safe-top    { padding-top: env(safe-area-inset-top, 16px); }
        .safe-bottom { padding-bottom: env(safe-area-inset-bottom, 16px); }
        body { background-color: #f9fafb; }

        .loading-overlay {
            position: fixed; inset: 0;
            background: rgba(255,255,255,0.7);
            display: flex; align-items: center; justify-content: center;
            z-index: 9999;
        }
        .spinner {
            width: 32px; height: 32px;
            border: 3px solid #e0e7ff;
            border-top-color: #4f46e5;
            border-radius: 50%;
            animation: spin 0.6s linear infinite;
        }
        @keyframes spin { to { transform: rotate(360deg); } }
    </style>
    @livewireStyles
</head>
<body class="bg-gray-100 antialiased font-sans">

{{-- Em vez daquela div enorme que cobre o ecrã, usa isto: --}}
<div wire:loading.delay class="fixed top-0 left-0 right-0 h-1 bg-indigo-600 z-[9999] animate-pulse"></div>


<main class="pb-24">
    {{ $slot }}
</main>

@if(!request()->is('login') && !request()->is('register'))
    <nav class="fixed bottom-4 left-0 right-0 z-50">
        <div class="max-w-md mx-auto px-4">
            <div class="bg-white/90 backdrop-blur-md border border-gray-200 rounded-3xl shadow-xl flex items-center justify-around py-3 safe-bottom">

                <button onclick="history.back()"
                        class="flex flex-col items-center gap-1 px-4 text-gray-400 active:scale-90 transition-transform {{ request()->is('/') ? 'opacity-20 pointer-events-none' : '' }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M15 19l-7-7 7-7"/>
                    </svg>
                    <span class="text-[10px] font-bold uppercase tracking-wider">Voltar</span>
                </button>

                {{-- MUDANÇA 2: Adicionado .hover para pre-load --}}
                <a href="/" wire:navigate.hover
                   class="flex flex-col items-center gap-1 px-4 transition-all {{ request()->is('/') ? 'text-indigo-600' : 'text-gray-400' }}">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                    </svg>
                    <span class="text-[10px] font-bold uppercase tracking-wider">Início</span>
                </a>

                <form method="POST" action="/logout" class="m-0">
                    @csrf
                    <button type="submit" class="flex flex-col items-center gap-1 px-4 text-gray-400 active:scale-90 transition-transform">
                        <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>
                        </svg>
                        <span class="text-[10px] font-bold uppercase tracking-wider">Sair</span>
                    </button>
                </form>
            </div>
        </div>
    </nav>
@endif

@livewireScripts
</body>
</html>
