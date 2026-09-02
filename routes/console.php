<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

/*
|--------------------------------------------------------------------------
| Durées de conservation (L09, Tableau 12)
|--------------------------------------------------------------------------
| Exécutés hors heures de consultation. Le préavis précède la purge d'une
| demi-heure ; la purge du journal est hebdomadaire. Le déclenchement du
| planificateur dans le conteneur est décrit dans
| docs/exploitation/durees-de-conservation.md (service scheduler, L12).
*/
Schedule::command('comptes:preavis-inactivite')->dailyAt('03:00')->withoutOverlapping();
Schedule::command('comptes:purger-inactifs')->dailyAt('03:30')->withoutOverlapping();
Schedule::command('audit:purger')->weeklyOn(7, '04:00')->withoutOverlapping();
