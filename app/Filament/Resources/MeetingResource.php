<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MeetingResource\Pages;
use App\Filament\Resources\MeetingResource\RelationManagers;
use App\Models\Meeting;
use App\Models\User;
use DateTime;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Grid;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;

class MeetingResource extends Resource
{
    protected static ?string $model = Meeting::class;

    protected static ?string $modelLabel = 'Reunión';
    protected static ?string $pluralLabel = 'Reuniones';

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';
    
    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
                Grid::make(3)->schema([
                    Forms\Components\Select::make('user_id')
                        ->label('Usuario')
                        ->options(fn () => User::role('Usuario')->pluck('name', 'id'))
                        ->default(fn () => auth()->user() ? auth()->id() : null)
                        ->hidden(fn () => \Illuminate\Support\Facades\Auth::user()->hasRole('Usuario'))
                        ->hidden(fn () => auth()->user()->hasRole('Usuario'))
                        ->disabled(fn () => auth()->user()->hasRole('Usuario')),
                    Forms\Components\Hidden::make('user_id')
                        ->default(fn () => auth()->user()->hasRole('Usuario') ? auth()->id() : null)
                        ->visible(fn () => auth()->user()->hasRole('Usuario')),
                    Forms\Components\DateTimePicker::make('meeting_date')
                        ->label('Fecha de la reunión')
                        ->required()
                        ->minDate(now())
                        ->default(now())
                        ->placeholder('Seleccione una fecha y hora')
                        ->displayFormat('d/m/Y H:i'),
                    Forms\Components\Select::make('meeting_status')
                        ->label('Estado de la reunión')
                        ->options([
                            'requested' => 'Solicitada',
                            'accepted' => 'Aceptada',
                            'finished' => 'Finalizada',
                            'cancelled' => 'Cancelada',
                        ])
                        ->default('requested')
                        ->required()
                        ->hiddenOn(['create']),                      
                ]),
                  
                Forms\Components\TextInput::make('subject')
                    ->label('Asunto')
                    ->required()
                    ->columnSpan('full')
                    ->placeholder('Ingrese el asunto de la reunión'),
                Forms\Components\Textarea::make('details')
                    ->label('Detalles')
                    ->required()
                    ->placeholder('Ingrese los detalles de la reunión')
                    ->columnSpan('full'),
                Forms\Components\TextInput::make('url')
                    ->label('URL de la reunión')
                    ->required()
                    ->placeholder('Ingrese la URL de la reunión')
                    ->url()
                    ->columnSpan('full'),
                Forms\Components\TextInput::make('client_name')
                    ->label('Nombre del cliente')
                    ->required()
                    ->placeholder('Ingrese el nombre del cliente'),
                Forms\Components\TextInput::make('client_email')
                    ->label('Email del cliente')
                    ->required()
                    ->placeholder('Ingrese el email del cliente')
                    ->email(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('meeting_date')
                    ->label('Fecha')
                    ->dateTime('d/m/Y H:i')
                    ->sortable()
                    ->searchable(),
                TextColumn::make('subject')
                    ->label('Asunto')
                    ->sortable()
                    ->searchable()
                    ->wrap(),
                TextColumn::make('client_name')
                    ->label('Cliente')
                    ->sortable()
                    ->searchable()
                    ->wrap(),
                TextColumn::make('user.name')
                    ->label('Usuario')
                    ->sortable()
                    ->searchable()
                    ->wrap(),
                    IconColumn::make('meeting_status')
                    ->label('Estado')
                    ->color(fn ($state) => match ($state) {
                        'requested' => 'warning',
                        'accepted' => 'success',
                        'finished' => 'success',
                        'cancelled' => 'danger',
                    })
                    ->icon(fn ($state) => match ($state) {
                            'requested' => 'heroicon-o-clock',
                            'accepted' => 'heroicon-o-clock',
                            'finished' => 'heroicon-o-check-circle',
                            'cancelled' => 'heroicon-o-x-circle',
                    })
                    ->tooltip(fn ($state) => match ($state) {
                        'requested' => 'Solicitada',
                        'accepted' => 'Aceptada',
                        'finished' => 'Finalizada',
                        'cancelled' => 'Cancelada',
                    })
            ])

            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->label('')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->tooltip('Ver')
                    ->iconSize('h-6 w-6'),
                Tables\Actions\EditAction::make()
                    ->label('')
                    ->icon('heroicon-o-pencil-square')
                    ->color('warning')
                    ->tooltip('Editar')
                    ->iconSize('h-6 w-6'),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Tables\Actions\Action::make('create')
                    ->label('Crear reunión')
                    ->url(static::getUrl('create'))
                    ->icon('heroicon-o-plus'),
            ])
            ->emptyStateDescription('Cree una reunión para empezar.');
            
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListMeetings::route('/'),
            'create' => Pages\CreateMeeting::route('/create'),
            'view' => Pages\ViewMeeting::route('/{record}'),
            'edit' => Pages\EditMeeting::route('/{record}/edit'),
        ];
    }
    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        if (auth()->user()->hasRole('Usuario')) {
            return $query->where('user_id', auth()->id());
    }
        return $query;
    }
}