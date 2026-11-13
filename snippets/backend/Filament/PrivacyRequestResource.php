<?php

/*
 * Resource de Filament v3 para gestionar solicitudes ARCOP con estado de seguimiento.
 * Decisión: los datos del titular son de solo lectura; el estado solo se
 * puede cambiar cuando la identidad ya fue verificada, y la creación desde
 * el panel está deshabilitada (refuerza lo que dice la Policy).
 */

namespace App\Filament\Resources;

use App\Models\PrivacyRequest;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
// ...

class PrivacyRequestResource extends Resource
{
    protected static ?string $model = PrivacyRequest::class;

    // ...

    private const STATUS_LABELS = [
        PrivacyRequest::STATUS_PENDING_VERIFICATION => 'Pendiente de Verificación',
        PrivacyRequest::STATUS_PENDING_PROCESSING => 'Pendiente de Procesamiento',
        PrivacyRequest::STATUS_RESOLVED => 'Resuelto',
    ];

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make('Datos de la Solicitud')
                ->schema([
                    TextInput::make('email')->label('Correo')->disabled(),
                    Select::make('type')->label('Tipo')->options(PrivacyRequest::TYPES)->disabled(),
                    // ...
                ])->columns(2),

            Section::make('Gestión')->schema([
                Select::make('status')
                    ->label('Estado')
                    // Una solicitud sin verificar no se puede gestionar hasta que el titular confirme su identidad
                    ->options(fn (?PrivacyRequest $record): array => $record?->status === PrivacyRequest::STATUS_PENDING_VERIFICATION
                        ? self::STATUS_LABELS
                        : [
                            PrivacyRequest::STATUS_PENDING_PROCESSING => self::STATUS_LABELS[PrivacyRequest::STATUS_PENDING_PROCESSING],
                            PrivacyRequest::STATUS_RESOLVED => self::STATUS_LABELS[PrivacyRequest::STATUS_RESOLVED],
                        ])
                    ->disabled(fn (?PrivacyRequest $record): bool => $record?->status === PrivacyRequest::STATUS_PENDING_VERIFICATION)
                    ->selectablePlaceholder(false)
                    ->native(false),
            ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('email')->label('Correo')->searchable(),
                TextColumn::make('status')
                    ->label('Estado')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => self::STATUS_LABELS[$state] ?? $state)
                    ->color(fn (string $state): string => match ($state) {
                        PrivacyRequest::STATUS_PENDING_PROCESSING => 'danger',
                        PrivacyRequest::STATUS_RESOLVED => 'success',
                        default => 'gray',
                    }),
                // ...
            ])
            ->defaultSort('created_at', 'desc');
        // ... filtros y acciones
    }
}
