@props(['target' => 'password'])

{{--
    Indicateur visuel de force du mot de passe.

    Fonctionne en pur Alpine.js (déjà embarqué par Livewire 4), sans aucune
    requête réseau : chaque frappe est évaluée côté client via des regex qui
    reflètent la politique définie dans App\Providers\AppServiceProvider
    (Password::min(12)->mixedCase()->numbers()->symbols()).

    Usage : placer juste après le champ <input wire:model="password" ...>
        <x-password-strength target="password" />

    Le prop "target" doit correspondre à l'id/name du champ mot de passe
    à surveiller (utile si jamais plusieurs champs de mot de passe existent
    sur la même page, ex. "new_password").
--}}
<div
    x-data="{
        value: '',
        get rules() {
            return [
                { label: '12 caractères minimum', valid: this.value.length >= 12 },
                { label: 'Une minuscule',         valid: /[a-z]/.test(this.value) },
                { label: 'Une majuscule',         valid: /[A-Z]/.test(this.value) },
                { label: 'Un chiffre',            valid: /[0-9]/.test(this.value) },
                { label: 'Un caractère spécial',  valid: /[^A-Za-z0-9]/.test(this.value) },
            ];
        },
        get score() {
            return this.rules.filter(r => r.valid).length;
        },
        get scoreLabel() {
            if (this.value.length === 0) return '';
            if (this.score <= 2) return 'Faible';
            if (this.score <= 4) return 'Moyen';
            return 'Fort';
        },
        get scoreColor() {
            if (this.score <= 2) return 'bg-red-500';
            if (this.score <= 4) return 'bg-amber-500';
            return 'bg-emerald-500';
        },
        init() {
            const field = document.getElementById('{{ $target }}');
            if (!field) return;
            this.value = field.value;
            field.addEventListener('input', (e) => { this.value = e.target.value; });
        },
    }"
    class="mt-2.5 space-y-2"
    x-show="value.length > 0"
    x-cloak
>
    {{-- Barre de progression --}}
    <div class="flex items-center gap-2">
        <div class="h-1.5 flex-1 overflow-hidden rounded-full bg-zinc-800">
            <div
                class="h-full rounded-full transition-all duration-300 ease-out"
                :class="scoreColor"
                :style="`width: ${(score / 5) * 100}%`"
            ></div>
        </div>
        <span
            class="w-12 shrink-0 text-right text-[11px] font-bold uppercase tracking-wide"
            :class="{
                'text-red-400': score <= 2,
                'text-amber-400': score > 2 && score <= 4,
                'text-emerald-400': score > 4,
            }"
            x-text="scoreLabel"
        ></span>
    </div>

    {{-- Détail des critères --}}
    <ul class="grid grid-cols-1 gap-x-4 gap-y-1 sm:grid-cols-2">
        <template x-for="rule in rules" :key="rule.label">
            <li class="flex items-center gap-1.5 text-xs transition-colors" :class="rule.valid ? 'text-emerald-400' : 'text-zinc-500'">
                <svg x-show="rule.valid" x-cloak class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="3">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M5 13l4 4L19 7" />
                </svg>
                <svg x-show="!rule.valid" class="h-3.5 w-3.5 shrink-0" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="9" />
                </svg>
                <span x-text="rule.label"></span>
            </li>
        </template>
    </ul>
</div>
