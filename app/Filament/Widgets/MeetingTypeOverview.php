<?php

namespace App\Filament\Widgets;

use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;
use App\Models\Meeting;

class MeetingTypeOverview extends BaseWidget
{
    protected function getStats(): array
    {
        return [
            Stat::make('Soliictadas', Meeting::query()->where('meeting_status', 'requested')->count()),
            Stat::make('Aceptadas', Meeting::query()->where('meeting_status', 'accepted')->count()),
            Stat::make('Finalizadas', Meeting::query()->where('meeting_status', 'finished')->count()),
            Stat::make('Canceladas', Meeting::query()->where('meeting_status', 'cancelled')->count()),
        ];
    }
}
