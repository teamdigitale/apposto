<?php

namespace Database\Seeders;

use App\Models\Presence;
use App\Models\Project;
use App\Models\ProjectRequest;
use App\Models\User;
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * DemoSeeder — dati fittizi per APPosto
 *
 * Crea:
 *  - 1 superuser (admin)
 *  - 1 project manager
 *  - 8 utenti base
 *  - 3 progetti attivi + 1 chiuso
 *  - Assegnazione utenti ai progetti con ruoli realistici
 *  - Presenze degli ultimi 3 mesi (ferie, permessi, smart working, presente)
 *  - Alcune project request pending per testare il flusso approvazione
 *
 * Utilizzo:
 *   php artisan db:seed --class=DemoSeeder
 *
 * ATTENZIONE: cancella tutti i dati esistenti nelle tabelle coinvolte.
 */
class DemoSeeder extends Seeder
{
    // Password univoca per tutti gli utenti demo → cambiala prima di usare in staging
    private const DEMO_PASSWORD = 'password';

    public function run(): void
    {
        $this->command->info('🧹  Pulizia tabelle...');
        $this->truncateTables();

        $this->command->info('👤  Creazione utenti...');
        [$admin, $pm, $users] = $this->createUsers();

        $this->command->info('📁  Creazione progetti...');
        $projects = $this->createProjects();

        $this->command->info('🔗  Assegnazione utenti ai progetti...');
        $this->assignUsersToProjects($admin, $pm, $users, $projects);

        $this->command->info('📅  Generazione presenze (ultimi 3 mesi)...');
        $this->generatePresences($users, $pm, $projects);

        $this->command->info('📬  Creazione project request di test...');
        $this->createProjectRequests($users, $projects);

        $this->command->info('');
        $this->command->info('✅  Demo seed completato!');
        $this->command->info('');
        $this->command->table(
            ['Ruolo', 'Email', 'Password'],
            [
                ['Super Admin',      'admin@apposto.test',   self::DEMO_PASSWORD],
                ['Project Manager',  'pm@apposto.test',      self::DEMO_PASSWORD],
                ['Utente base',      'mario@apposto.test',   self::DEMO_PASSWORD],
                ['Utente base',      '...(altri 7)',         self::DEMO_PASSWORD],
            ]
        );
    }

    // -----------------------------------------------------------------------
    // Pulizia
    // -----------------------------------------------------------------------

    private function truncateTables(): void
    {
        DB::statement('PRAGMA foreign_keys = OFF;'); // SQLite
        // Per MySQL/PostgreSQL usa: DB::statement('SET FOREIGN_KEY_CHECKS=0;');

        ProjectRequest::truncate();
        Presence::truncate();
        DB::table('project_user')->truncate();
        Project::truncate();
        User::withTrashed()->forceDelete(); // soft delete

        DB::statement('PRAGMA foreign_keys = ON;');
    }

    // -----------------------------------------------------------------------
    // Utenti
    // -----------------------------------------------------------------------

    private function createUsers(): array
    {
        // Super admin
        $admin = User::create([
            'name'                => 'Admin Sistema',
            'email'               => 'admin@apposto.test',
            'password'            => Hash::make(self::DEMO_PASSWORD),
            'email_verified_at'   => now(),
            'superuser'           => true,
            'is_project_manager'  => false,
            'allow_view'          => true,
            'gestiamopresenze'    => true,
            'ferie_totali'        => 30,
            'giorni_in_smart'     => 3,
            'priority'            => 10,
        ]);

        // Project manager (può approvare richieste dei suoi progetti)
        $pm = User::create([
            'name'                => 'Laura Conti',
            'email'               => 'pm@apposto.test',
            'password'            => Hash::make(self::DEMO_PASSWORD),
            'email_verified_at'   => now(),
            'superuser'           => false,
            'is_project_manager'  => true,
            'allow_view'          => true,
            'gestiamopresenze'    => true,
            'ferie_totali'        => 26,
            'giorni_in_smart'     => 2,
            'priority'            => 5,
        ]);

        // Team di sviluppo — nomi italiani realistici
        $userData = [
            ['Mario Rossi',      'mario@apposto.test',    26, 3],
            ['Giulia Ferretti',  'giulia@apposto.test',   28, 2],
            ['Luca Bianchi',     'luca@apposto.test',     26, 3],
            ['Sofia Marino',     'sofia@apposto.test',    26, 2],
            ['Davide Ricci',     'davide@apposto.test',   30, 3],
            ['Anna Lombardi',    'anna@apposto.test',     26, 2],
            ['Marco Gallo',      'marco@apposto.test',    26, 3],
            ['Chiara Esposito',  'chiara@apposto.test',   26, 2],
        ];

        $users = collect();
        foreach ($userData as [$name, $email, $ferie, $smart]) {
            $users->push(User::create([
                'name'               => $name,
                'email'              => $email,
                'password'           => Hash::make(self::DEMO_PASSWORD),
                'email_verified_at'  => now(),
                'superuser'          => false,
                'is_project_manager' => false,
                'allow_view'         => true,
                'gestiamopresenze'   => true,
                'ferie_totali'       => $ferie,
                'giorni_in_smart'    => $smart,
                'priority'           => 1,
            ]));
        }

        return [$admin, $pm, $users];
    }

    // -----------------------------------------------------------------------
    // Progetti
    // -----------------------------------------------------------------------

    private function createProjects(): \Illuminate\Support\Collection
    {
        $data = [
            [
                'name'              => 'Portale Servizi Digitali',
                'description'       => 'Sviluppo del nuovo portale web per i servizi al cittadino. Integrazione con SPID, CIE e PagoPA.',
                'start_date'        => '2025-09-01',
                'end_date'          => '2026-08-31',
                'active'            => true,
                'slack_channel'     => 'https://teams.microsoft.com/portale-servizi',
                'documentation_url' => 'https://confluence.example.gov.it/portale',
            ],
            [
                'name'              => 'App Mobile Comune',
                'description'       => 'Applicazione mobile React Native per accedere ai servizi comunali da smartphone.',
                'start_date'        => '2025-11-01',
                'end_date'          => '2026-06-30',
                'active'            => true,
                'slack_channel'     => 'https://teams.microsoft.com/app-mobile',
                'documentation_url' => 'https://confluence.example.gov.it/app-mobile',
            ],
            [
                'name'              => 'Migrazione Infrastruttura Cloud',
                'description'       => 'Migrazione dei sistemi legacy on-premise verso il cloud nazionale PSNC.',
                'start_date'        => '2026-01-15',
                'end_date'          => '2026-12-31',
                'active'            => true,
                'slack_channel'     => 'https://teams.microsoft.com/cloud-migration',
                'documentation_url' => 'https://notion.example.gov.it/cloud',
                'resources_notes'   => 'Credenziali ambiente di test nel vault. Contatto referente PSNC: ing. Caruso.',
            ],
            [
                'name'              => 'Sistema Gestione Documenti (archiviato)',
                'description'       => 'Progetto concluso. Sistema di archiviazione documentale con firma digitale P7M.',
                'start_date'        => '2025-01-01',
                'end_date'          => '2025-07-31',
                'active'            => false,
            ],
        ];

        return collect($data)->map(fn($d) => Project::create($d));
    }

    // -----------------------------------------------------------------------
    // Assegnazione utenti → progetti con ruoli
    // -----------------------------------------------------------------------

    private function assignUsersToProjects(User $admin, User $pm, $users, $projects): void
    {
        [$portale, $app, $cloud, $archiviato] = $projects->values()->all();

        // Ruoli disponibili nel sistema
        // Laura Conti (PM) è manager su tutti e 3 i progetti attivi
        foreach ([$portale, $app, $cloud] as $proj) {
            $pm->projects()->attach($proj->id, [
                'role'       => 'manager',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        // Progetto 1: Portale Servizi Digitali — 6 persone
        $portaleTeam = [
            [$users[0], 'developer'],       // Mario
            [$users[1], 'developer'],       // Giulia
            [$users[2], 'designer'],        // Luca
            [$users[3], 'product owner'],   // Sofia
            [$users[4], 'scrum master'],    // Davide
            [$users[5], 'developer'],       // Anna
        ];
        foreach ($portaleTeam as [$user, $role]) {
            $user->projects()->attach($portale->id, [
                'role'       => $role,
                'created_at' => now()->subMonths(rand(2, 6)),
                'updated_at' => now(),
            ]);
        }

        // Progetto 2: App Mobile — 5 persone (overlap con portale)
        $appTeam = [
            [$users[0], 'developer'],       // Mario (su due progetti)
            [$users[2], 'developer'],       // Luca
            [$users[5], 'designer'],        // Anna
            [$users[6], 'developer'],       // Marco
            [$users[7], 'tester'],          // Chiara
        ];
        foreach ($appTeam as [$user, $role]) {
            $user->projects()->attach($app->id, [
                'role'       => $role,
                'created_at' => now()->subMonths(rand(1, 4)),
                'updated_at' => now(),
            ]);
        }

        // Progetto 3: Cloud Migration — 4 persone
        $cloudTeam = [
            [$users[3], 'developer'],       // Sofia
            [$users[4], 'developer'],       // Davide
            [$users[6], 'scrum master'],    // Marco
            [$users[7], 'developer'],       // Chiara
        ];
        foreach ($cloudTeam as [$user, $role]) {
            $user->projects()->attach($cloud->id, [
                'role'       => $role,
                'created_at' => now()->subMonths(rand(1, 3)),
                'updated_at' => now(),
            ]);
        }

        // Progetto archiviato — solo per storico
        $users[0]->projects()->attach($archiviato->id, [
            'role' => 'developer', 'created_at' => now()->subYear(), 'updated_at' => now()->subMonths(6),
        ]);
        $users[1]->projects()->attach($archiviato->id, [
            'role' => 'designer',  'created_at' => now()->subYear(), 'updated_at' => now()->subMonths(6),
        ]);
    }

    // -----------------------------------------------------------------------
    // Presenze — ultimi 3 mesi, pattern realistici per persona
    // -----------------------------------------------------------------------

    private function generatePresences($users, User $pm, $projects): void
    {
        $start = now()->subMonths(3)->startOfMonth();
        $end   = now()->endOfMonth();

        // Tutti gli utenti con presenza (PM + 8 base)
        $allUsers = $users->prepend($pm);

        // Pattern per utente: distribuzione tipica della settimana
        // [presente%, smart%, ferie%, permesso%, assenza_totale%]
        $patterns = [
            // Mario — presente spesso, qualche smart
            0 => ['presente' => 45, 'smart_working' => 40, 'ferie' => 10, 'permesso' => 5],
            // Giulia — molto smart working
            1 => ['presente' => 25, 'smart_working' => 55, 'ferie' => 15, 'permesso' => 5],
            // Luca — bilanciato
            2 => ['presente' => 40, 'smart_working' => 40, 'ferie' => 15, 'permesso' => 5],
            // Sofia — ferie estese (va in ferie a blocchi)
            3 => ['presente' => 35, 'smart_working' => 35, 'ferie' => 25, 'permesso' => 5],
            // Davide — molto presente in ufficio
            4 => ['presente' => 60, 'smart_working' => 25, 'ferie' => 10, 'permesso' => 5],
            // Anna
            5 => ['presente' => 40, 'smart_working' => 40, 'ferie' => 15, 'permesso' => 5],
            // Marco — spesso in smart
            6 => ['presente' => 20, 'smart_working' => 60, 'ferie' => 15, 'permesso' => 5],
            // Chiara — pochi dati inseriti (utente pigra nel segnare)
            7 => ['presente' => 30, 'smart_working' => 20, 'ferie' => 8, 'permesso' => 2],
            // PM Laura
            8 => ['presente' => 50, 'smart_working' => 30, 'ferie' => 15, 'permesso' => 5],
        ];

        // Festività italiane approssimative da evitare
        $holidays = $this->italianHolidaysFlat(now()->year - 1, now()->year, now()->year + 1);

        $period = CarbonPeriod::create($start, $end);

        foreach ($allUsers->values() as $idx => $user) {
            $pattern = $patterns[$idx] ?? $patterns[0];

            // Costruisci pool di status in base al pattern
            $pool = [];
            foreach ($pattern as $status => $weight) {
                for ($i = 0; $i < $weight; $i++) {
                    $pool[] = $status;
                }
            }

            // Per Sofia: genera un blocco ferie continuativo a gennaio
            $ferieBlock = null;
            if ($idx === 3) {
                $ferieBlock = [
                    Carbon::create(now()->year, 1, 13),
                    Carbon::create(now()->year, 1, 17),
                ];
            }
            // Per Marco: blocco smart working
            $swBlock = null;
            if ($idx === 6) {
                $swBlock = [
                    Carbon::create(now()->year, 2, 3),
                    Carbon::create(now()->year, 2, 14),
                ];
            }

            foreach ($period as $date) {
                // Salta weekend e festivi
                if (!$date->isWeekday()) continue;
                if (in_array($date->format('Y-m-d'), $holidays)) continue;

                // Chiara: salta ~50% dei giorni (non segna tutto)
                if ($idx === 7 && rand(0, 1) === 0) continue;

                // Blocco ferie Sofia
                if ($ferieBlock && $date->between($ferieBlock[0], $ferieBlock[1])) {
                    $status = 'ferie';
                } elseif ($swBlock && $date->between($swBlock[0], $swBlock[1])) {
                    $status = 'smart_working';
                } else {
                    $status = $pool[array_rand($pool)];
                }

                Presence::updateOrCreate(
                    ['user_id' => $user->id, 'date' => $date->format('Y-m-d')],
                    ['status'  => $status]
                );
            }
        }
    }

    // -----------------------------------------------------------------------
    // Project Request — alcune pending per testare il flusso
    // -----------------------------------------------------------------------

    private function createProjectRequests($users, $projects): void
    {
        [$portale, $app, $cloud] = $projects->values()->all();

        // Utente non ancora nel progetto cloud chiede di unirsi
        ProjectRequest::create([
            'user_id'    => $users[1]->id, // Giulia (non è nel cloud)
            'project_id' => $cloud->id,
            'type'       => 'join',
            'status'     => 'pending',
            'role'       => 'developer',
            'message'    => 'Sono interessata alla migrazione cloud, ho esperienza con Kubernetes e Terraform.',
        ]);

        // Utente che vuole lasciare il progetto portale
        ProjectRequest::create([
            'user_id'    => $users[5]->id, // Anna
            'project_id' => $portale->id,
            'type'       => 'leave',
            'status'     => 'pending',
            'message'    => 'Il mio contratto scade a fine mese, chiedo di essere rimossa dal progetto.',
        ]);

        // Richiesta già approvata (storico)
        ProjectRequest::create([
            'user_id'     => $users[2]->id, // Luca
            'project_id'  => $app->id,
            'type'        => 'join',
            'status'      => 'approved',
            'role'        => 'developer',
            'message'     => 'Disponibile per supportare il team app mobile.',
            'reviewed_by' => $users[3]->id,
            'reviewed_at' => now()->subDays(10),
        ]);

        // Richiesta rifiutata (storico)
        ProjectRequest::create([
            'user_id'     => $users[7]->id, // Chiara
            'project_id'  => $portale->id,
            'type'        => 'join',
            'status'      => 'rejected',
            'role'        => 'tester',
            'admin_notes' => 'Il team è al completo per questa fase del progetto.',
            'reviewed_by' => $users[4]->id,
            'reviewed_at' => now()->subDays(5),
        ]);
    }

    // -----------------------------------------------------------------------
    // Helper: festività italiane (lista piatta di date YYYY-MM-DD)
    // -----------------------------------------------------------------------

    private function italianHolidaysFlat(int ...$years): array
    {
        $dates = [];
        foreach ($years as $year) {
            $fixed = [
                "$year-01-01", "$year-01-06", "$year-04-25",
                "$year-05-01", "$year-06-02", "$year-08-15",
                "$year-11-01", "$year-12-08", "$year-12-25", "$year-12-26",
            ];
            // Pasqua e Pasquetta (algoritmo di Gauss)
            $easter = Carbon::createFromTimestamp(easter_date($year));
            $fixed[] = $easter->format('Y-m-d');
            $fixed[] = $easter->addDay()->format('Y-m-d');

            $dates = array_merge($dates, $fixed);
        }
        return $dates;
    }
}