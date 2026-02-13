<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UafeConfiguracionResource\Pages;
use App\Models\UafeConfiguracion;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class UafeConfiguracionResource extends Resource
{
    protected static ?string $model = UafeConfiguracion::class;

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Configuración UAFE';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('Plantilla de correo UAFE')
                ->schema([
                    Forms\Components\TextInput::make('nombre')
                        ->required()
                        ->maxLength(255),
                    Forms\Components\Toggle::make('activo')
                        ->default(true),
                    Forms\Components\TextInput::make('plantilla_asunto')
                        ->required()
                        ->helperText('Placeholders disponibles: {{proveedor.nombre}}, {{proveedor.ruc}}, {{empresa.nombre}}, {{link_carga_documentos}}'),
                    Forms\Components\RichEditor::make('plantilla_cuerpo')
                        ->required()
                        ->toolbarButtons([
                            'bold',
                            'italic',
                            'underline',
                            'strike',
                            'bulletList',
                            'orderedList',
                            'h2',
                            'h3',
                            'blockquote',
                            'link',
                            'redo',
                            'undo',
                        ])
                        ->columnSpanFull(),
                    Forms\Components\FileUpload::make('adjuntos_fijos')
                        ->multiple()
                        ->disk('public')
                        ->directory('uafe/plantillas')
                        ->label('Adjuntos fijos')
                        ->helperText('Adjuntos informativos que se enviarán en cada notificación.'),
                ])->columns(2),

            Forms\Components\Section::make('Configuración de correo SMTP')
                ->schema([
                    Forms\Components\TextInput::make('smtp_host')->label('Host SMTP'),
                    Forms\Components\TextInput::make('smtp_puerto')->numeric()->label('Puerto SMTP'),
                    Forms\Components\TextInput::make('smtp_usuario')->label('Usuario SMTP'),
                    Forms\Components\TextInput::make('smtp_password')->password()->label('Contraseña SMTP')->revealable(),
                    Forms\Components\Select::make('smtp_cifrado')
                        ->options([
                            'tls' => 'TLS',
                            'ssl' => 'SSL',
                            '' => 'Sin cifrado',
                        ])
                        ->label('Cifrado'),
                    Forms\Components\TextInput::make('smtp_from_name')->label('Nombre remitente'),
                    Forms\Components\TextInput::make('smtp_from_email')->email()->label('Email remitente'),
                    Forms\Components\TextInput::make('smtp_timeout')->numeric()->label('Timeout (segundos)'),
                ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nombre')->searchable(),
                Tables\Columns\IconColumn::make('activo')->boolean(),
                Tables\Columns\TextColumn::make('smtp_host')->label('Host SMTP'),
                Tables\Columns\TextColumn::make('smtp_from_email')->label('From email'),
                Tables\Columns\TextColumn::make('updated_at')->dateTime('Y-m-d H:i')->sortable(),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\DeleteBulkAction::make(),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUafeConfiguracions::route('/'),
            'create' => Pages\CreateUafeConfiguracion::route('/create'),
            'edit' => Pages\EditUafeConfiguracion::route('/{record}/edit'),
        ];
    }
}
