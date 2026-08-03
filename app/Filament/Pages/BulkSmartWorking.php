<?php

namespace App\Filament\Pages;

use App\Helpers\Holidays;
use App\Models\Presence;
use App\Models\User;
use Carbon\Carbon;
use Filament\Actions\Action;
use Filament\Forms;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

class BulkSmartWorking extends Page
{
    protected static ?string $navigationIcon  = 'heroicon-o-computer-desktop';
    protected static ?string $navigationLabel = 'Smart Working Massivo';
    protected static ?string $navigationGroup = 'Azioni di Sistema';
    protected static ?int    $navigationSort  = 1;
    protected static string  $view            = 'filament.pages.bulk-smart-working';

    // Campi del form gestiti da Livewire
    public string $month     = '';
    public bool   $overwrite = false;

    /**
     * Solo i superuser possono accedere a questa pagina.
     */
    

    public function mount(): void
    {
       
        $this->month = now()->format('Y-m');
    }

    protected function getHeaderActions(): array
    {
        return [
            Action::make('applica')
                ->label('Applica Smart Working')
                ->icon('heroicon-o-check-circle')
                ->color('success')
                ->requiresConfirmation()
                ->modalHeading('Conferma applicazione massiva')
                ->modalDescription(function () {
                    $label = Carbon::parse($this->month)->translatedFormat('F Y');
                    $note  = $this->overwrite
                        ? 'I giorni già compilati verranno sovrascritti.'
                        : 'I giorni già compilati NON verranno toccati.';
                    return "Verranno impostati tutti i giorni lavorativi di {$label} come Smart Working per tutti gli utenti attivi. {$note}";
                })
                ->modalSubmitActionLabel('Sì, applica')
                ->action(fn () => $this->apply()),
        ];
    }

    public function apply(): void
    {
      
        $start = Carbon::parse($this->month)->startOfMonth();
        $end   = Carbon::parse($this->month)->endOfMonth();

        // Giorni lavorativi non festivi
        $workDays = [];
        $cursor   = $start->copy();
        while ($cursor->lte($end)) {
            if ($cursor->isWeekday() && !Holidays::isHoliday($cursor)) {
                $workDays[] = $cursor->format('Y-m-d');
            }
            $cursor->addDay();
        }

        if (empty($workDays)) {
            Notification::make()
                ->title('Nessun giorno lavorativo trovato.')
                ->warning()
                ->send();
            return;
        }

        $users   = User::where('allow_view', true)->get();
        $created = 0;
        $skipped = 0;

        DB::transaction(function () use ($users, $workDays, &$created, &$skipped) {
            foreach ($users as $user) {
                foreach ($workDays as $date) {
                    $exists = Presence::where('user_id', $user->id)
                        ->where('date', $date)
                        ->exists();

                    if ($exists && !$this->overwrite) {
                        $skipped++;
                        continue;
                    }

                    Presence::updateOrCreate(
                        ['user_id' => $user->id, 'date'    => $date],
                        ['status'  => 'smart_working', 'booking_id' => null, 'workplace_id' => null]
                    );
                    $created++;
                }
            }
        });

        Notification::make()
            ->title('Smart Working applicato!')
            ->body("{$created} presenze impostate" . ($skipped ? ", {$skipped} saltate (già compilate)" : '') . '.')
            ->success()
            ->send();
    }
}