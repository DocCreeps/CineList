<?php

use Illuminate\Support\Facades\Schedule;

// Compte démo : efface les bacs à sable expirés (nécessite le scheduler : `schedule:run` chaque minute).
Schedule::command('demo:purge')->hourly();

// Fiches de films devenues orphelines (plus aucun membre ne les référence) : nettoyage hebdomadaire.
Schedule::command('movies:purge-orphans')->weekly();
