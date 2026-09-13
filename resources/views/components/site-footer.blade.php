{{--
    Footer principal, utilisé par le layout sur toutes les pages.
    Contient l'attribution TMDB obligatoire (texte + logo) — ne pas retirer,
    voir https://www.themoviedb.org/api-terms-of-use.
--}}
<footer class="mx-auto mt-10 max-w-7xl border-t border-zinc-900 px-4 py-6 sm:px-8 lg:px-12">
    <div class="flex flex-col items-center gap-2.5 text-center sm:flex-row sm:justify-between sm:text-left">
        <a href="https://www.themoviedb.org/" target="_blank" rel="noopener" class="flex items-center gap-2 opacity-80 transition hover:opacity-100">
            <img src="https://www.themoviedb.org/assets/v4/logos/v2/blue_square_1-5bdc75aaebeb75dc7ae79426ddd9be3b2be1e342510f8202baf6bffa71d7f5c4.svg" alt="TMDB" class="h-5 w-auto">
            <span class="text-xs text-zinc-500">Données et images fournies par The Movie Database (TMDB)</span>
        </a>
        <p class="max-w-md text-[11px] leading-relaxed text-zinc-600">
            This product uses the TMDB API but is not endorsed or certified by TMDB.
        </p>
    </div>
</footer>
