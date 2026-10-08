<?php

namespace App\Console\Commands;

use App\Actions\Demo\PurgeDemoSandboxes;
use Illuminate\Console\Command;

class PurgeDemoSandboxesCommand extends Command
{
    protected $signature = 'demo:purge';

    protected $description = 'Supprime les comptes fictifs (bacs à sable) de la démo arrivés à expiration';

    public function handle(PurgeDemoSandboxes $purge): int
    {
        $this->info($purge->handle().' bac(s) à sable supprimé(s).');

        return self::SUCCESS;
    }
}
