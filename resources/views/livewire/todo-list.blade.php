<div class="max-w-md mx-auto min-h-screen bg-gray-50 pb-24 shadow-2xl">

    {{-- Header --}}
    <div class="bg-indigo-700 p-6 rounded-b-3xl shadow-lg mb-4">
        <div class="flex justify-between items-center">
            <div>
                <h1 class="text-2xl font-bold text-white flex items-center gap-2">
                    <span>📝</span> Todo Sync
                </h1>
                <p class="text-indigo-200 text-sm">Olá, {{ auth()->user()->name }}</p>
            </div>
            <form method="POST" action="/logout">
                @csrf
                <button type="submit" class="text-indigo-200 text-xs underline">Sair</button>
            </form>
        </div>
    </div>

    @if(session('success'))
        <div class="mx-4 mb-4 bg-green-100 text-green-700 px-4 py-3 rounded-2xl text-sm font-medium">
            ✓ {{ session('success') }}
        </div>
    @endif

    <div class="px-4">

        {{-- Botão abrir formulário --}}
        <button
            wire:click="$toggle('showForm')"
            class="w-full flex items-center justify-between bg-white border border-gray-200
                   rounded-2xl px-5 py-4 mb-4 shadow-sm text-gray-500 hover:border-indigo-300
                   transition-colors"
        >
            <span class="text-sm">Nova tarefa...</span>
            <span class="text-2xl text-indigo-600 font-bold">{{ $showForm ? '−' : '+' }}</span>
        </button>

        {{-- Formulário de criação --}}
        @if($showForm)
            <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4 mb-4">
                <form wire:submit.prevent="addTodo" class="space-y-3">

                    <input
                        type="text"
                        wire:model="task"
                        placeholder="Título da tarefa *"
                        class="w-full p-3 rounded-xl border border-gray-200 bg-gray-50
                           focus:ring-2 focus:ring-indigo-500 text-gray-800 text-sm"
                    />
                    @error('task')
                    <p class="text-red-500 text-xs">{{ $message }}</p>
                    @enderror

                    <textarea
                        wire:model="description"
                        placeholder="Descrição (opcional)"
                        rows="2"
                        class="w-full p-3 rounded-xl border border-gray-200 bg-gray-50
                           focus:ring-2 focus:ring-indigo-500 text-gray-800 text-sm resize-none"
                    ></textarea>

                    {{-- Toggle repetir --}}
                    <div class="flex items-center gap-3">
                        <button
                            type="button"
                            wire:click="$toggle('is_recurring')"
                            class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors
                               {{ $is_recurring ? 'bg-indigo-600' : 'bg-gray-300' }}"
                        >
                        <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform
                                     {{ $is_recurring ? 'translate-x-6' : 'translate-x-1' }}"></span>
                        </button>
                        <span class="text-sm text-gray-600">Repetir</span>
                    </div>

                    @if($is_recurring)
                        <div class="flex gap-2 flex-wrap">
                            @foreach($diasSemana as $num => $label)
                                <button
                                    type="button"
                                    wire:click="toggleDay({{ $num }})"
                                    class="px-3 py-1.5 rounded-xl text-xs font-semibold border-2 transition-colors
                                       {{ in_array($num, $recurring_days)
                                           ? 'bg-indigo-600 border-indigo-600 text-white'
                                           : 'bg-white border-gray-200 text-gray-500' }}"
                                >
                                    {{ $label }}
                                </button>
                            @endforeach
                        </div>
                    @endif

                    <div class="flex gap-2 pt-1">
                        <button
                            type="button"
                            wire:click="$set('showForm', false)"
                            class="flex-1 py-3 bg-gray-100 text-gray-600 rounded-xl text-sm font-semibold"
                        >
                            Cancelar
                        </button>
                        <button
                            type="submit"
                            class="flex-1 py-3 bg-indigo-600 text-white rounded-xl text-sm font-semibold"
                        >
                            Criar
                        </button>
                    </div>
                </form>
            </div>
        @endif

        {{-- Os meus todos --}}
        <div class="space-y-3 mb-6">
            @forelse($myTodos as $todo)
                <div class="bg-white rounded-2xl shadow-sm border border-gray-100 p-4">
                    <div class="flex items-start gap-3">

                        {{-- Checkbox --}}
                        <button
                            wire:click="toggleTodo({{ $todo->id }})"
                            class="w-6 h-6 mt-0.5 rounded-full border-2 flex-shrink-0 flex items-center
                                   justify-center transition-colors
                                   {{ $todo->completed_today
                                       ? 'bg-indigo-600 border-indigo-600'
                                       : 'border-gray-300' }}"
                        >
                            @if($todo->completed_today)
                                <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                </svg>
                            @endif
                        </button>

                        {{-- Conteúdo --}}
                        <div class="flex-1 min-w-0">
                            {{-- Link para detalhe --}}
                            <a href="/todo/{{ $todo->id }}" class="block">
                                <p class="font-semibold text-gray-800 {{ $todo->completed_today ? 'line-through text-gray-400' : '' }}">
                                    {{ $todo->task }}
                                </p>
                                @if($todo->description)
                                    <p class="text-xs text-gray-400 mt-0.5 truncate">{{ $todo->description }}</p>
                                @endif
                            </a>

                            {{-- Dias da semana --}}
                            @if($todo->is_recurring)
                                @php $selectedDays = $todo->getRecurringDaysArray(); @endphp
                                <div class="flex gap-1 mt-1.5 flex-wrap">
                                    @foreach([1=>'Seg',2=>'Ter',3=>'Qua',4=>'Qui',5=>'Sex',6=>'Sáb',7=>'Dom'] as $n => $l)
                                        <span class="text-xs px-1.5 py-0.5 rounded-md font-medium
                                            {{ in_array($n, $selectedDays)
                                                ? 'bg-indigo-100 text-indigo-700'
                                                : 'bg-gray-100 text-gray-300' }}">
                                            {{ $l }}
                                        </span>
                                    @endforeach
                                </div>

                                {{-- 7 círculos --}}
                                <div class="flex gap-1.5 items-center mt-2">
                                    @foreach($todo->seven_days as $day)
                                        <div class="w-5 h-5 rounded-full
                                            {{ $day['done']
                                                ? 'bg-indigo-600'
                                                : ($day['today'] ? 'border-2 border-indigo-400' : 'bg-gray-200') }}">
                                        </div>
                                    @endforeach
                                    <span class="text-xs text-gray-400 ml-1">{{ $todo->days_done }}/7</span>
                                </div>
                            @endif
                        </div>

                        {{-- Acções --}}
                        <div class="flex flex-col gap-2 flex-shrink-0">
                            <a href="/todo/{{ $todo->id }}/edit"
                               class="text-blue-400 text-base leading-none">✏️</a>
                            <button
                                wire:click="deleteTodo({{ $todo->id }})"
                                wire:confirm="Tens a certeza que queres eliminar?"
                                class="text-red-400 font-bold text-base leading-none">✕</button>
                        </div>
                    </div>
                </div>
            @empty
                <div class="text-center py-12">
                    <p class="text-4xl mb-3">✅</p>
                    <p class="text-gray-500 font-medium">Nenhuma tarefa ainda.</p>
                    <p class="text-gray-400 text-sm mt-1">Toca no + para criar a primeira.</p>
                </div>
            @endforelse
        </div>

        {{-- Partilhados comigo --}}
        @if($sharedTodos->count() > 0)
            <p class="text-xs text-gray-400 font-semibold uppercase tracking-wide mb-3">
                Partilhados comigo
            </p>
            <div class="space-y-3">
                @foreach($sharedTodos as $todo)
                    <div class="bg-white rounded-2xl shadow-sm border border-indigo-100 p-4">
                        <div class="flex items-start gap-3">
                            <button
                                wire:click="toggleTodo({{ $todo->id }})"
                                class="w-6 h-6 mt-0.5 rounded-full border-2 flex-shrink-0 flex items-center
                                       justify-center transition-colors
                                       {{ $todo->completed_today
                                           ? 'bg-indigo-600 border-indigo-600'
                                           : 'border-gray-300' }}"
                            >
                                @if($todo->completed_today)
                                    <svg class="w-3 h-3 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                                    </svg>
                                @endif
                            </button>
                            <div class="flex-1">
                                <a href="/todo/{{ $todo->id }}" class="block">
                                    <p class="font-semibold text-gray-800 {{ $todo->completed_today ? 'line-through text-gray-400' : '' }}">
                                        {{ $todo->task }}
                                    </p>
                                    <p class="text-xs text-indigo-400 mt-0.5">de {{ $todo->user->name }}</p>
                                </a>
                                @if($todo->is_recurring && count($todo->seven_days) > 0)
                                    <div class="flex gap-1.5 items-center mt-2">
                                        @foreach($todo->seven_days as $day)
                                            <div class="w-5 h-5 rounded-full
                                                {{ $day['done'] ? 'bg-indigo-600' : ($day['today'] ? 'border-2 border-indigo-400' : 'bg-gray-200') }}">
                                            </div>
                                        @endforeach
                                        <span class="text-xs text-gray-400 ml-1">{{ $todo->days_done }}/7</span>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>
