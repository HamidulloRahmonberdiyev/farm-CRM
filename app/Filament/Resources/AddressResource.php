<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AddressResource\Pages;
use App\Filament\Resources\AddressResource\RelationManagers;
use App\Models\Address;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class AddressResource extends Resource
{
    protected static ?string $model = Address::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getModelLabel(): string
    {
        return __('models/address.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('models/address.plural');
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('models/address.prop.name'))
                    ->required()
                    ->extraInputAttributes([
                        'x-data' => '',
                        'x-on:input' => '$el.value = $el.value.charAt(0).toUpperCase() + $el.value.slice(1)',
                    ])
                    ->maxLength(255),
                Forms\Components\TextInput::make('second_name')
                    ->label(__('models/address.prop.second_name'))
                    ->nullable()
                    ->extraInputAttributes([
                        'x-data' => '',
                        'x-on:input' => '$el.value = $el.value.charAt(0).toUpperCase() + $el.value.slice(1)',
                    ])
                    ->maxLength(255),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('models/address.prop.name'))
                    ->searchable(),
                Tables\Columns\TextColumn::make('second_name')
                    ->label(__('models/address.prop.second_name'))
                    ->searchable(),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListAddresses::route('/'),
            'create' => Pages\CreateAddress::route('/create'),
            'edit' => Pages\EditAddress::route('/{record}/edit'),
        ];
    }
}
