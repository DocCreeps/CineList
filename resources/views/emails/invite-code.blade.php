<x-mail::message>
# Tu es invité(e) sur {{ config('app.name') }}

Quelqu'un t'a invité(e) à rejoindre {{ config('app.name') }}, l'application de gestion de liste de films à voir.

Utilise le code ci-dessous lors de ton inscription :

<x-mail::panel>
## {{ $code }}
</x-mail::panel>

@if ($expiresAt)
Ce code expire le **{{ $expiresAt->format('d/m/Y') }}**.
@else
Ce code n'expire pas, mais il ne peut être utilisé qu'une seule fois.
@endif

<x-mail::button :url="$registerUrl">
Créer mon compte
</x-mail::button>

Si tu n'es pas à l'origine de cette invitation, tu peux ignorer cet e-mail sans risque.

Merci,<br>
{{ config('app.name') }}
</x-mail::message>
