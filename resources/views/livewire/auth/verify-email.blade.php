<div>
    <h1 class="font-display text-3xl tracking-wide text-zinc-100">VÉRIFIEZ VOTRE E-MAIL</h1>
    <p class="mt-3 text-sm leading-relaxed text-zinc-400">
        Un lien de confirmation a été envoyé à <span class="font-semibold text-zinc-200">{{ auth()->user()->email }}</span>.
        Cliquez dessus pour activer votre compte.
    </p>

    @if ($sent)
    <div class="mt-4 rounded-xl border border-emerald-800/40 bg-emerald-950/30 px-3.5 py-2.5 text-sm text-emerald-300">
        E-mail de vérification renvoyé.
    </div>
    @endif

    <div class="mt-6 flex items-center gap-3">
        <button wire:click="resend" class="rounded-xl bg-amber-500 px-4 py-2.5 text-sm font-bold text-zinc-950 shadow-lg shadow-amber-950/40 transition hover:bg-amber-400">
            Renvoyer l'e-mail
        </button>
        <form method="POST" action="{{ route('logout') }}">
            @csrf
            <button type="submit" class="text-xs font-semibold text-zinc-500 hover:text-zinc-300 transition">Se déconnecter</button>
        </form>
    </div>
</div>
