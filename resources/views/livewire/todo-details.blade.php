<div class="max-w-md mx-auto min-h-screen bg-gray-50">

    {{-- Header --}}
    <div class="bg-indigo-700 px-6 pb-8 rounded-b-3xl shadow-lg mb-6"
         style="padding-top: max(32px, env(safe-area-inset-top));">
        <h1 class="text-2xl font-bold text-white">{{ $todo['task'] }}</h1>
        @if($todo['description'])
            <p class="text-indigo-200 text-sm mt-1 opacity-90">{{ $todo['description'] }}</p>
        @endif
    </div>

    <div class="px-4 space-y-4 pb-28">

        {{-- Estatísticas --}}
        <div class="grid grid-cols-3 gap-3">
            <div class="bg-white rounded-2xl p-4 text-center border border-gray-100 shadow-sm">
                <p class="text-2xl font-black text-indigo-600">{{ $stats['this_week'] ?? 0 }}</p>
                <p class="text-[10px] text-gray-400 uppercase font-bold mt-1">Semana</p>
            </div>
            <div class="bg-white rounded-2xl p-4 text-center border border-gray-100 shadow-sm">
                <p class="text-2xl font-black text-indigo-600">{{ $stats['this_month'] ?? 0 }}</p>
                <p class="text-[10px] text-gray-400 uppercase font-bold mt-1">Mês</p>
            </div>
            <div class="bg-white rounded-2xl p-4 text-center border border-gray-100 shadow-sm">
                <p class="text-2xl font-black text-indigo-600">{{ $stats['total'] ?? 0 }}</p>
                <p class="text-[10px] text-gray-400 uppercase font-bold mt-1">Total</p>
            </div>
        </div>

        {{-- Frequência/Repetição --}}
        @if($todo['is_recurring'] ?? false)
            <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm">
                <p class="text-xs text-gray-400 font-semibold uppercase mb-3 tracking-wider">Frequência</p>
                <div class="flex items-center gap-3 mb-4">
                    <div class="relative inline-flex h-6 w-11 items-center rounded-full bg-indigo-600">
                        <span class="inline-block h-4 w-4 transform rounded-full bg-white translate-x-6"></span>
                    </div>
                    <span class="text-sm font-medium text-gray-700">Tarefa Recorrente</span>
                </div>
                <div class="flex gap-2 flex-wrap">
                    @foreach([1=>'Seg',2=>'Ter',3=>'Qua',4=>'Qui',5=>'Sex',6=>'Sáb',7=>'Dom'] as $n => $l)
                        <span class="px-3 py-2 rounded-xl text-xs font-bold border-2
                            {{ in_array($n, $todo['recurring_days'] ?? [])
                                ? 'bg-indigo-600 border-indigo-600 text-white'
                                : 'bg-gray-50 border-gray-100 text-gray-300' }}">
                            {{ $l }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Últimos 7 dias --}}
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm">
            <p class="text-xs text-gray-400 font-semibold uppercase mb-3 tracking-wider">Últimos 7 dias</p>
            <div class="flex justify-between items-center">
                @foreach($sevenDays as $day)
                    <div class="flex flex-col items-center gap-2">
                        <span class="text-[10px] font-bold text-gray-400 uppercase">{{ substr($day['label'], 0, 3) }}</span>
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center text-sm font-bold
                            {{ $day['done']
                                ? 'bg-indigo-600 text-white shadow-md shadow-indigo-100'
                                : (($day['today'] ?? false) ? 'border-2 border-indigo-400 text-indigo-600' : 'bg-gray-100 text-gray-400') }}">
                            {{ \Carbon\Carbon::parse($day['date'])->day }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Secção: Partilhado com (Visual Catita) --}}
        @if(count($todo['shared_with'] ?? []) > 0)
            <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm">
                <p class="text-xs text-gray-400 font-semibold uppercase mb-3 tracking-wider">Partilhado com</p>
                <div class="space-y-3">
                    @foreach($todo['shared_with'] as $user)
                        <div class="flex items-center gap-3 p-2 rounded-2xl bg-gray-50 border border-gray-100">
                            <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center border-2 border-white shadow-sm flex-shrink-0">
                                <span class="text-indigo-600 text-sm font-black">
                                    {{ strtoupper(substr($user['name'], 0, 1)) }}
                                </span>
                            </div>
                            <div class="flex flex-col min-w-0">
                                <p class="text-sm font-bold text-gray-800 leading-tight truncate">{{ $user['name'] }}</p>
                                <p class="text-[11px] text-gray-500 truncate">{{ $user['email'] }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Botão Editar --}}
        @if(($todo['user_id'] ?? null) === session('api_user')['id'])
            <div class="pt-4">
                <a href="/todo/{{ $todo['id'] }}/edit" wire:navigate.hover
                   class="block w-full py-4 bg-indigo-600 text-white text-center font-black uppercase tracking-widest
                          rounded-2xl shadow-xl shadow-indigo-100 active:scale-95 transition-transform">
                    Editar Tarefa
                </a>
            </div>
        @endif

    </div>
</div>
