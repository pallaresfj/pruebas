<?php

namespace App\Filament\Resources;

use App\Filament\Resources\MeetingResource\Pages;
use App\Models\Meeting;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Table;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\Action;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

/**
 * Resource para la gestión de reuniones.
 */

class MeetingResource extends Resource
{
    protected static ?string $model = Meeting::class;
    protected static ?string $modelLabel = 'Reunión';
    protected static ?string $pluralLabel = 'Reuniones';
    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    // Estados posibles de la reunión
    public const STATUS_REQUESTED = 'requested';
    public const STATUS_ACCEPTED = 'accepted';
    public const STATUS_FINISHED = 'finished';
    public const STATUS_CANCELLED = 'cancelled';

    public const STATUS_OPTIONS = [
        self::STATUS_REQUESTED => 'Solicitada',
        self::STATUS_ACCEPTED => 'Aceptada',
        self::STATUS_FINISHED => 'Finalizada',
        self::STATUS_CANCELLED => 'Cancelada',
    ];

    public static function form(Form $form): Form
    {
        $isUsuario = self::isUsuario();
        return $form->schema([
            Grid::make(3)->schema([
                // Campo user_id: visible solo para administradores
                Select::make('user_id')
                    ->label('Usuario')
                    ->options(fn() => User::role('Usuario')->pluck('name', 'id'))
                    ->default(fn() => Auth::user() ? Auth::id() : null)
                    ->hidden(fn() => $isUsuario)
                    ->disabled(fn() => $isUsuario),
                // Campo user_id oculto: visible solo para usuarios
                Hidden::make('user_id')
                    ->default(fn() => $isUsuario ? Auth::id() : null)
                    ->visible(fn() => $isUsuario),
                DateTimePicker::make('meeting_date')
                    ->label('Fecha de la reunión')
                    ->required()
                    ->minDate(now())
                    ->default(now())
                    ->placeholder('Seleccione una fecha y hora')
                    ->displayFormat('d/m/Y H:i'),
                Select::make('meeting_status')
                    ->label('Estado de la reunión')
                    ->options(self::STATUS_OPTIONS)
                    ->default(self::STATUS_REQUESTED)
                    ->required()
                    ->hiddenOn(['create']),
            ]),
            TextInput::make('subject')
                ->label('Asunto')
                ->required()
                ->columnSpan('full')
                ->placeholder('Ingrese el asunto de la reunión'),
            Textarea::make('details')
                ->label('Detalles')
                ->required()
                ->placeholder('Ingrese los detalles de la reunión')
                ->columnSpan('full'),
            TextInput::make('url')
                ->label('URL de la reunión')
                ->required()
                ->placeholder('Ingrese la URL de la reunión')
                ->url()
                ->columnSpan('full'),
            TextInput::make('client_name')
                ->label('Nombre del cliente')
                ->required()
                ->placeholder('Ingrese el nombre del cliente'),
            TextInput::make('client_email')
                ->label('Email del cliente')
                ->required()
                ->placeholder('Ingrese el email del cliente')
                ->email(),
        ]);
    }

    /**
     * Determina si el usuario autenticado tiene el rol 'Usuario'.
     */
    protected static function isUsuario(): bool
    {
        $user = Auth::user();
        return $user && $user->hasRole('Usuario');
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns(self::getTableColumns())
            ->filters([])
            ->actions(self::getTableActions())
            ->bulkActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ])
            ->emptyStateActions([
                Action::make('create')
                    ->label('Crear reunión')
                    ->url(static::getUrl('create'))
                    ->icon('heroicon-o-plus'),
            ])
            ->emptyStateDescription('Cree una reunión para empezar.');
    }

    /**
     * Devuelve las columnas de la tabla de reuniones.
     */
    protected static function getTableColumns(): array
    {
        return [
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
                ->color(fn($state) => match ($state) {
                    self::STATUS_REQUESTED => 'warning',
                    self::STATUS_ACCEPTED => 'success',
                    self::STATUS_FINISHED => 'success',
                    self::STATUS_CANCELLED => 'danger',
                })
                ->icon(fn($state) => match ($state) {
                    self::STATUS_REQUESTED => 'heroicon-o-clock',
                    self::STATUS_ACCEPTED => 'heroicon-o-clock',
                    self::STATUS_FINISHED => 'heroicon-o-check-circle',
                    self::STATUS_CANCELLED => 'heroicon-o-x-circle',
                })
                ->tooltip(fn($state) => self::STATUS_OPTIONS[$state] ?? $state),
        ];
    }

    /**
     * Devuelve las acciones de la tabla de reuniones.
     */
    protected static function getTableActions(): array
    {
        return [
            ViewAction::make()
                ->label('')
                ->icon('heroicon-o-eye')
                ->color('info')
                ->tooltip('Ver')
                ->iconSize('h-6 w-6'),
            EditAction::make()
                ->label('')
                ->icon('heroicon-o-pencil-square')
                ->color('warning')
                ->tooltip('Editar')
                ->iconSize('h-6 w-6'),
        ];
    }

    public static function getRelations(): array
    {
        return [];
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
        if (self::isUsuario()) {
            return $query->where('user_id', Auth::id());
        }
        return $query;
    }
}
