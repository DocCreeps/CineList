<div>
    <h1 class="font-display text-3xl tracking-wide text-zinc-100">MOT DE PASSE OUBLIÉ</h1>
    <p class="mt-1 text-sm text-zinc-500">On vous envoie un lien de réinitialisation par e-mail.</p>

    @if ($sent)
    <div class="mt-6 rounded-xl border border-emerald-800/40 bg-emerald-950/30 px-3.5 py-2.5 text-sm text-emerald-300">
        Si un compte existe avec cette adresse, un lien de réinitialisation vient d'être envoyé.
    </div>
    @else
    <form wire:submit="sendResetLink" class="mt-6 space-y-4">
        <div>
            <label for="email" class="mb-1.5 block text-xs font-bold uppercase tracking-wide text-zinc-500">E-mail</label>
            <input wire:model="email" id="email" type="email" autocomplete="username" required autofocus
                class="w-full rounded-xl border border-zinc-700 bg-zinc-950 px-3.5 py-2.5 text-sm text-zinc-100 focus:border-amber-500 focus:outline-none focus:ring-1 focus:ring-amber-500">
            @error('email') <p class="mt-1.5 text-xs text-red-400">{{ $message }}</p> @enderror
        </div>

        <button type="submit" class="w-full rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-bold text-zinc-950 shadow-lg shadow-amber-950/40 transition hover:bg-amber-400">
            Envoyer le lien
        </button>
    </form>
    @endif

    <div class="mt-5 text-center text-xs text-zinc-500">
        <a href="{{ route('login') }}" wire:navigate class="hover:text-amber-400 transition">← Retour à la connexion</a>
    </div>
</div>
