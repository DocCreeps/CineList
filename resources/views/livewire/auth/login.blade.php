<div>
    <h1 class="font-display text-3xl tracking-wide text-zinc-100">CONNEXION</h1>
    <p class="mt-1 text-sm text-zinc-500">Accédez à votre liste de films.</p>

    @if (session('notice'))
    <div class="mt-4 rounded-xl border border-emerald-800/40 bg-emerald-950/30 px-3.5 py-2.5 text-sm text-emerald-300">
        {{ session('notice') }}
    </div>
    @endif

    <form wire:submit="login" class="mt-6 space-y-4">
        <div>
            <label for="email" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-zinc-500">E-mail</label>
            <input wire:model="email" id="email" type="email" autocomplete="username" required autofocus
                class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3.5 py-2.5 text-sm text-zinc-100 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
            @error('email') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-zinc-500">Mot de passe</label>
            <input wire:model="password" id="password" type="password" autocomplete="current-password" required
                class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3.5 py-2.5 text-sm text-zinc-100 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
            @error('password') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
        </div>

        <label class="flex items-center gap-2 text-xs text-zinc-400">
            <input wire:model="remember" type="checkbox" class="rounded border-zinc-700 bg-zinc-950 text-amber-500 focus:ring-amber-500">
            Se souvenir de moi
        </label>

        <button type="submit" class="w-full rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-bold text-zinc-950 shadow-lg shadow-amber-950/40 transition hover:bg-amber-400">
            Se connecter
        </button>
    </form>

    <div class="mt-5 flex items-center justify-between text-xs text-zinc-500">
        <a href="{{ route('password.request') }}" wire:navigate class="hover:text-amber-400 transition">Mot de passe oublié ?</a>
        <a href="{{ route('register') }}" wire:navigate class="hover:text-amber-400 transition">Créer un compte →</a>
    </div>
</div>
