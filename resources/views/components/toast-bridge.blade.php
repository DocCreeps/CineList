{{--
    Relaie session('notice') (posé avant une redirection plein-page, ex. réinitialisation du mot
    de passe → connexion) vers le store "toast", pour qu'il s'affiche comme n'importe quel autre
    toast plutôt que dans un encart de page. Sans @persist dans les layouts, wire:navigate
    reconstruit cet élément à chaque nouvelle page : x-init s'exécute donc à chaque fois, y
    compris d'une redirection à l'autre.
--}}
@if (session('notice'))
<div x-data x-init="$store.toast.push(@js(session('notice')), @js(session('notice_type', 'success')))"></div>
@endif
