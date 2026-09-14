<div>
    <h1 class="font-display text-3xl tracking-wide text-zinc-100">CRÉER UN COMPTE</h1>
    <p class="mt-1 text-sm text-zinc-500">Sur invitation uniquement.</p>

    <form wire:submit="register" class="mt-6 space-y-4">
        <div>
            <label for="name" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-zinc-500">Nom</label>
            <input wire:model="name" id="name" type="text" autocomplete="name" required autofocus
                class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3.5 py-2.5 text-sm text-zinc-100 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
            @error('name') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="email" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-zinc-500">E-mail</label>
            <input wire:model="email" id="email" type="email" autocomplete="username" required
                class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3.5 py-2.5 text-sm text-zinc-100 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
            @error('email') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-zinc-500">Mot de passe</label>
            <input wire:model="password" id="password" type="password" autocomplete="new-password" required
                class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3.5 py-2.5 text-sm text-zinc-100 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
            <x-password-strength target="password" />
            @error('password') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
        </div>

        <div>
            <label for="password_confirmation" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-zinc-500">Confirmer le mot de passe</label>
            <input wire:model="password_confirmation" id="password_confirmation" type="password" autocomplete="new-password" required
                class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3.5 py-2.5 text-sm text-zinc-100 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
        </div>

        <div>
            <label for="invite_code" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-zinc-500">Code d'invitation</label>
            <input wire:model="invite_code" id="invite_code" type="text" required placeholder="XXXX-XXXX"
                class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3.5 py-2.5 font-mono text-sm uppercase text-zinc-100 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
            @error('invite_code') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="w-full rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-bold text-zinc-950 shadow-lg shadow-amber-950/40 transition hover:bg-amber-400">
            Créer mon compte
        </button>
    </form>

    <div class="mt-5 text-center text-xs text-zinc-500">
        <a href="{{ route('login') }}" wire:navigate class="hover:text-amber-400 transition">Déjà un compte ? Se connecter</a>
    </div>
</div>
