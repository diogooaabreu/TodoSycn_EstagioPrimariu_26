<div class="max-w-md mx-auto min-h-screen bg-gray-50 pb-24 shadow-2xl">

    {{-- Header --}}
    <div class="bg-indigo-700 p-6 rounded-b-3xl shadow-lg mb-6">
        <a href="/todo/{{ $todo->id }}" class="text-indigo-200 text-sm">← Voltar</a>
        <h1 class="text-xl font-bold text-white mt-2">Editar Todo</h1>
    </div>

    <div class="px-4 space-y-4">

        {{-- Secção: Detalhes --}}
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm">
            <p class="text-xs text-gray-400 font-semibold uppercase mb-3">Detalhes</p>

            <div class="space-y-3">
                <div>
                    <label class="text-sm font-medium text-gray-700 mb-1 block">Título</label>
                    <input
                        type="text"
                        wire:model="task"
                        class="w-full p-3 rounded-xl border border-gray-200 bg-gray-50
                               focus:ring-2 focus:ring-indigo-500 text-gray-800 text-sm"
                    />
                    @error('task')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="text-sm font-medium text-gray-700 mb-1 block">Descrição</label>
                    <textarea
                        wire:model="description"
                        rows="3"
                        placeholder="Descrição opcional..."
                        class="w-full p-3 rounded-xl border border-gray-200 bg-gray-50
                               focus:ring-2 focus:ring-indigo-500 text-gray-800 text-sm resize-none"
                    ></textarea>
                </div>
            </div>
        </div>

        {{-- Secção: Repetição --}}
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm">
            <p class="text-xs text-gray-400 font-semibold uppercase mb-3">Repetição</p>

            <div class="flex items-center gap-3 mb-3">
                <button
                    type="button"
                    wire:click="$toggle('is_recurring')"
                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors
                           {{ $is_recurring ? 'bg-indigo-600' : 'bg-gray-300' }}"
                >
                    <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform
                                 {{ $is_recurring ? 'translate-x-6' : 'translate-x-1' }}"></span>
                </button>
                <span class="text-sm text-gray-600">Repetir diariamente</span>
            </div>

            @if($is_recurring)
                <div class="flex gap-2 flex-wrap">
                    @foreach($diasSemana as $num => $label)
                        <button
                            type="button"
                            wire:click="toggleDay({{ $num }})"
                            class="px-3 py-2 rounded-xl text-sm font-semibold border-2 transition-colors
                                   {{ in_array($num, $recurring_days)
                                       ? 'bg-indigo-600 border-indigo-600 text-white'
                                       : 'bg-white border-gray-200 text-gray-500' }}"
                        >
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Secção: Partilhas --}}
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm">
            <p class="text-xs text-gray-400 font-semibold uppercase mb-3">Partilhas</p>

            {{-- Mensagens --}}
            @if($shareMessage)
                <div class="bg-green-100 text-green-700 px-3 py-2 rounded-xl mb-3 text-sm">
                    ✓ {{ $shareMessage }}
                </div>
            @endif
            @if($shareError)
                <div class="bg-red-100 text-red-600 px-3 py-2 rounded-xl mb-3 text-sm">
                    {{ $shareError }}
                </div>
            @endif

            {{-- Adicionar partilha --}}
            <div class="flex gap-2 mb-4">
                <input
                    type="email"
                    wire:model="shareEmail"
                    placeholder="email do utilizador..."
                    class="flex-1 p-3 rounded-xl border border-gray-200 bg-gray-50 text-sm text-gray-800"
                />
                <button
                    wire:click="addShare"
                    class="bg-indigo-600 text-white px-4 rounded-xl font-semibold text-sm active:bg-indigo-700"
                >
                    Adicionar
                </button>
            </div>

            {{-- Lista de partilhas --}}
            @if($todo->sharedWithUsers->count() > 0)
                <div class="space-y-2">
                    @foreach($todo->sharedWithUsers as $user)
                        <div class="flex items-center justify-between py-2 border-t border-gray-100">
                            <div class="flex items-center gap-3">
                                <div class="w-8 h-8 bg-indigo-100 rounded-full flex items-center justify-center">
                                    <span class="text-indigo-600 text-sm font-bold">
                                        {{ strtoupper(substr($user->name, 0, 1)) }}
                                    </span>
                                </div>
                                <div>
                                    <p class="text-sm font-medium text-gray-800">{{ $user->name }}</p>
                                    <p class="text-xs text-gray-400">{{ $user->email }}</p>
                                </div>
                            </div>
                            <button
                                wire:click="removeShare({{ $user->id }})"
                                wire:confirm="Remover partilha com {{ $user->name }}?"
                                class="text-red-400 text-xs font-semibold hover:text-red-600"
                            >
                                Remover
                            </button>
                        </div>
                    @endforeach
                </div>
            @else
                <p class="text-sm text-gray-400 text-center py-2">Ainda não partilhado com ninguém.</p>
            @endif
        </div>

        {{-- Guardar --}}
        <button
            wire:click="save"
            class="w-full py-4 bg-indigo-600 text-white font-bold rounded-2xl
                   shadow-lg active:bg-indigo-700 transition-colors"
        >
            Guardar Alterações
        </button>

    </div>
</div>
