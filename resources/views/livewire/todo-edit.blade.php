<div class="max-w-md mx-auto min-h-screen bg-gray-50">

    {{-- Header --}}
    <div class="bg-indigo-700 px-6 pb-8 rounded-b-3xl shadow-lg mb-6"
         style="padding-top: max(32px, env(safe-area-inset-top));">
        <h1 class="text-2xl font-bold text-white">Editar Tarefa</h1>
        <p class="text-indigo-200 text-sm opacity-80">Personaliza os detalhes e partilhas</p>
    </div>

    <div class="px-4 space-y-4 pb-28">

        {{-- Secção: Detalhes --}}
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm">
            <p class="text-xs text-gray-400 font-semibold uppercase mb-3 tracking-wider">Detalhes</p>
            <div class="space-y-4">
                <div>
                    <label class="text-sm font-medium text-gray-700 mb-1 block">Título</label>
                    <input type="text" wire:model="task"
                           class="w-full p-4 rounded-xl border border-gray-200 bg-gray-50
                               focus:ring-2 focus:ring-indigo-500 outline-none text-gray-800 text-sm transition-all"/>
                    @error('task')
                    <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                    @enderror
                </div>
                <div>
                    <label class="text-sm font-medium text-gray-700 mb-1 block">Descrição</label>
                    <textarea wire:model="description" rows="3" placeholder="O que precisas de fazer? (opcional)"
                              class="w-full p-4 rounded-xl border border-gray-200 bg-gray-50
                               focus:ring-2 focus:ring-indigo-500 outline-none text-gray-800 text-sm resize-none transition-all"></textarea>
                </div>
            </div>
        </div>

        {{-- Secção: Repetição --}}
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm">
            <p class="text-xs text-gray-400 font-semibold uppercase mb-3 tracking-wider">Repetição</p>
            <div class="flex items-center gap-3 mb-4">
                <button type="button" wire:click="$toggle('is_recurring')"
                        class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors
                           {{ $is_recurring ? 'bg-indigo-600' : 'bg-gray-300' }}">
                    <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform
                                 {{ $is_recurring ? 'translate-x-6' : 'translate-x-1' }}"></span>
                </button>
                <span class="text-sm font-medium text-gray-700">Repetir esta tarefa</span>
            </div>

            @if($is_recurring)
                <div class="flex gap-2 flex-wrap">
                    @foreach($diasSemana as $num => $label)
                        <button type="button" wire:click="toggleDay({{ $num }})"
                                class="px-3 py-2 rounded-xl text-xs font-bold border-2 transition-all
                                   {{ in_array($num, $recurring_days)
                                       ? 'bg-indigo-600 border-indigo-600 text-white shadow-md'
                                       : 'bg-white border-gray-100 text-gray-400' }}">
                            {{ $label }}
                        </button>
                    @endforeach
                </div>
            @endif
        </div>

        {{-- Secção: Partilhas (Visual Catita com Logos/Avatares) --}}
        <div class="bg-white rounded-2xl p-4 border border-gray-100 shadow-sm">
            <p class="text-xs text-gray-400 font-semibold uppercase mb-3 tracking-wider">Partilhado com</p>

            @if($shareMessage)
                <div class="bg-green-100 text-green-700 px-3 py-2 rounded-xl mb-3 text-sm font-medium animate-pulse">
                    ✓ {{ $shareMessage }}
                </div>
            @endif
            @if($shareError)
                <div class="bg-red-100 text-red-600 px-3 py-2 rounded-xl mb-3 text-sm font-medium">
                    {{ $shareError }}
                </div>
            @endif

            <div class="flex gap-2 mb-4">
                <input type="email" wire:model="shareEmail" placeholder="exemplo@email.com"
                       class="flex-1 p-3 rounded-xl border border-gray-200 bg-gray-50 text-sm text-gray-800 outline-none focus:ring-2 focus:ring-indigo-500"/>
                <button wire:click="addShare"
                        wire:loading.attr="disabled"
                        class="bg-indigo-600 text-white px-4 rounded-xl font-bold text-sm active:scale-95 transition-transform disabled:opacity-50">
                    <span wire:loading.remove wire:target="addShare">Adicionar</span>
                    <span wire:loading wire:target="addShare">...</span>
                </button>
            </div>

            @if(!empty($todo['shared_with']) && count($todo['shared_with']) > 0)
                <div class="space-y-3">
                    @foreach($todo['shared_with'] as $user)
                        <div class="flex items-center justify-between p-3 rounded-2xl bg-gray-50 border border-gray-100 transition-all">
                            <div class="flex items-center gap-3">
                                {{-- Avatar Catita --}}
                                <div class="w-10 h-10 bg-indigo-100 rounded-full flex items-center justify-center border-2 border-white shadow-sm flex-shrink-0">
                                    <span class="text-indigo-600 text-sm font-black">
                                        {{ strtoupper(substr($user['name'], 0, 1)) }}
                                    </span>
                                </div>
                                {{-- Info do Utilizador --}}
                                <div class="flex flex-col min-w-0">
                                    <p class="text-sm font-bold text-gray-800 leading-tight truncate">{{ $user['name'] }}</p>
                                    <p class="text-[11px] text-gray-500 truncate">{{ $user['email'] }}</p>
                                </div>
                            </div>
                            <button
                                wire:click="removeShare({{ $user['id'] }})"
                                wire:confirm="Remover a partilha com {{ $user['name'] }}?"
                                class="text-red-500 text-[11px] font-black uppercase tracking-tighter px-3 py-1.5 bg-red-50 rounded-lg active:bg-red-100 transition-colors">
                                Remover
                            </button>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-4 border-2 border-dashed border-gray-100 rounded-2xl">
                    <p class="text-sm text-gray-400 italic">Ainda não partilhado com ninguém.</p>
                </div>
            @endif
        </div>

        {{-- Botão Guardar --}}
        <div class="pt-2">
            <button wire:click="save"
                    wire:loading.attr="disabled"
                    class="w-full py-4 bg-indigo-600 text-white font-black rounded-2xl
                       shadow-xl shadow-indigo-200 active:bg-indigo-700 active:scale-[0.98] transition-all flex items-center justify-center gap-2">
                <span wire:loading.remove wire:target="save">GUARDAR ALTERAÇÕES</span>
                <span wire:loading wire:target="save" class="flex items-center gap-2">
                    <svg class="animate-spin h-5 w-5 text-white" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                    </svg>
                    A GUARDAR...
                </span>
            </button>
        </div>

    </div>
</div>
