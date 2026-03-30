<div class="max-w-md mx-auto min-h-screen bg-gray-50 pb-10 shadow-2xl">

    {{-- Header --}}
    <div class="bg-indigo-700 p-6 rounded-b-3xl shadow-lg mb-6">
        <h1 class="text-2xl font-bold text-white flex items-center gap-2">
            <span>📝</span> Mini Todo
        </h1>
        <p class="text-indigo-100 text-sm opacity-80">Cria a tua conta grátis</p>
    </div>

    <div class="px-4">

        <form wire:submit.prevent="register" class="space-y-4">

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Nome</label>
                <input
                    type="text"
                    wire:model="name"
                    placeholder="O teu nome"
                    class="w-full p-4 rounded-2xl border border-gray-200 shadow-sm
                           focus:ring-2 focus:ring-indigo-500 focus:border-transparent
                           bg-white text-gray-800"
                />
                @error('name')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Email</label>
                <input
                    type="email"
                    wire:model="email"
                    placeholder="o-teu@email.com"
                    class="w-full p-4 rounded-2xl border border-gray-200 shadow-sm
                           focus:ring-2 focus:ring-indigo-500 focus:border-transparent
                           bg-white text-gray-800"
                />
                @error('email')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <div>
                <label class="block text-sm font-medium text-gray-700 mb-1">Password</label>
                <input
                    type="password"
                    wire:model="password"
                    placeholder="Mínimo 6 caracteres"
                    class="w-full p-4 rounded-2xl border border-gray-200 shadow-sm
                           focus:ring-2 focus:ring-indigo-500 focus:border-transparent
                           bg-white text-gray-800"
                />
                @error('password')
                <p class="text-red-500 text-xs mt-1">{{ $message }}</p>
                @enderror
            </div>

            <button
                type="submit"
                class="w-full bg-indigo-600 text-white py-4 rounded-2xl font-bold
                       shadow-lg active:bg-indigo-700 transition-colors mt-2"
            >
                Criar Conta
            </button>
        </form>

        <p class="text-center text-gray-500 text-sm mt-6">
            Já tens conta?
            <a href="/login" class="text-indigo-600 font-semibold">Entrar aqui</a>
        </p>
    </div>
</div>
