<div class="max-w-md mx-auto min-h-screen bg-gray-50 pb-24 shadow-2xl">

    {{-- Header --}}
    <div class="bg-indigo-700 p-6 rounded-b-3xl shadow-lg mb-6">
        <div class="flex items-center gap-3">
            <a href="/" class="text-indigo-200 text-sm font-medium">← Voltar</a>
        </div>
        <h1 class="text-2xl font-bold text-white mt-3">{{ $todo->task }}</h1>
        @if($todo->description)
            <p class="text-indigo-200 text-sm mt-1">{{ $todo->description }}</p>
        @endif
    </div>

    <div class="px-4 space-y-4">

        {{-- Estatísticas --}}
        <div class="grid grid-cols-3 gap-3">
            <div class="bg-white rounded-2xl p-4 text-center border border-gray-100 shadow-sm">
                <p class="text-2xl font-bold text-indigo-600">{{ $stats['esta_semana'] }}</p>
                <p class="text-xs text-gray-400 mt-1">esta semana</p>
            </div>
            <div class="bg-white rounded-2xl p-4 text-center border border-gray-100 shadow-sm">
                <p class="text-2xl font-bold text-indigo-600">{{ $stats['este_mes'] }}</p>
                <p class="text-xs text-gray-400 mt-1">este mês</p>
            </div>
            <div class="bg-white rounded-2xl p-4 text-center border border-gray-100 shadow-sm">
                <p class="text-2xl font-bold text-indigo-600">{{ $stats['total'] }}</p>
                <p class="text-xs text-gray-400 mt-1">total</p>
            </div>
        </div>

        {{-- Dias da semana --}}
        @if($todo->is_recurring)
            @php $selectedDays = $todo->getRecurringDaysArray(); @endphp
            <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm">
                <p class="text-xs text-gray-400 font-semibold uppercase mb-3">Dias seleccionados</p>
                <div class="flex gap-2 flex-wrap">
                    @foreach([1=>'Segunda',2=>'Terça',3=>'Quarta',4=>'Quinta',5=>'Sexta',6=>'Sábado',7=>'Domingo'] as $n => $l)
                        <span class="text-xs px-3 py-1.5 rounded-xl font-medium
                            {{ in_array($n, $selectedDays)
                                ? 'bg-indigo-600 text-white'
                                : 'bg-gray-100 text-gray-300' }}">
                            {{ $l }}
                        </span>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Últimos 7 dias --}}
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm">
            <p class="text-xs text-gray-400 font-semibold uppercase mb-3">Últimos 7 dias</p>
            <div class="flex justify-between">
                @foreach($sevenDays as $day)
                    <div class="flex flex-col items-center gap-1.5">
                        <span class="text-xs text-gray-400">{{ strtoupper(substr($day['label'], 0, 3)) }}</span>
                        <div class="w-9 h-9 rounded-xl flex items-center justify-center text-sm font-semibold
                            {{ $day['done']
                                ? 'bg-indigo-600 text-white'
                                : ($day['today'] ? 'border-2 border-indigo-400 text-indigo-600' : 'bg-gray-100 text-gray-400') }}">
                            {{ \Carbon\Carbon::parse($day['date'])->day }}
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Histórico completo --}}
        @if(count($history) > 0)
            <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm">
                <p class="text-xs text-gray-400 font-semibold uppercase mb-3">Histórico completo</p>
                <div class="space-y-4">
                    @foreach($history as $mes)
                        <div>
                            <div class="flex justify-between items-center mb-2">
                                <p class="text-sm font-semibold text-gray-700 capitalize">{{ $mes['mes'] }}</p>
                                <span class="text-xs bg-indigo-100 text-indigo-700 px-2 py-0.5 rounded-full font-medium">
                                    {{ $mes['total'] }}x
                                </span>
                            </div>
                            <div class="flex flex-wrap gap-1.5">
                                @foreach($mes['dias'] as $dia)
                                    <span class="w-7 h-7 bg-indigo-600 text-white text-xs rounded-lg
                                                 flex items-center justify-center font-medium">
                                        {{ $dia }}
                                    </span>
                                @endforeach
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Partilhado com --}}
        @if($todo->user_id === auth()->id() && $todo->sharedWithUsers->count() > 0)
            <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm">
                <p class="text-xs text-gray-400 font-semibold uppercase mb-3">Partilhado com</p>
                <div class="space-y-2">
                    @foreach($todo->sharedWithUsers as $user)
                        <div class="flex items-center gap-3">
                            <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center flex-shrink-0">
                                <span class="text-indigo-600 text-sm font-bold">
                                    {{ strtoupper(substr($user->name, 0, 1)) }}
                                </span>
                            </div>
                            <div>
                                <p class="text-sm font-medium text-gray-800">{{ $user->name }}</p>
                                <p class="text-xs text-gray-400">{{ $user->email }}</p>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif

        {{-- Botão editar (só para o dono) --}}
        @if($todo->user_id === auth()->id())
            <a href="/todo/{{ $todo->id }}/edit"
               class="block w-full py-4 bg-indigo-600 text-white text-center font-bold
                      rounded-2xl shadow-lg active:bg-indigo-700 transition-colors">
                Editar Todo
            </a>
        @endif

    </div>
</div>
