<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CustomerResource\Pages;
use App\Filament\Resources\CustomerResource\RelationManagers;
use App\Filament\Resources\CustomerResource\RelationManagers\OrdersRelationManager;
use App\Models\Address;
use App\Models\Customer;
use Filament\Forms;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Support\Enums\VerticalAlignment;
use Filament\Tables;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;

class CustomerResource extends Resource
{
    protected static ?string $model = Customer::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getModelLabel(): string
    {
        return __('models/customer.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('models/customer.plural');
    }

    public static function form(Form $form): Form
    {
        $counter = 0;

        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\TextInput::make('first_name')
                            ->label((__('models/customer.prop.first_name')))
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('last_name')
                            ->label((__('models/customer.prop.last_name')))
                            ->maxLength(255),
                        Forms\Components\Select::make('address_id')
                            ->label((__('models/customer.prop.address')))
                            ->relationship('address', 'name'),
                        Forms\Components\DatePicker::make('date_of_birth')
                            ->label((__('models/customer.prop.date_of_birth'))),
                        Forms\Components\Hidden::make('age')
                            ->nullable(),
                        Forms\Components\Repeater::make('phones')
                            ->label((__('models/customer.prop.phones')))
                            ->relationship('phones')
                            ->schema([
                                Forms\Components\TextInput::make('phone')
                                    ->label(false)
                                    ->default('+998')
                                    ->mask('+999 99 999-99-99')
                                    ->tel()
                                    ->telRegex('/^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\.\/0-9]*$/')
                                    ->required(),
                            ])
                            ->itemLabel(function () use (&$counter) {
                                $number = ++$counter;
                                return $number === 1 ? __('models/customer.prop.phone') : __('models/customer.prop.additional_phone');
                            })
                            ->addActionLabel(__('models/customer.prop.add_phone'))
                            ->reorderable(false),
                        Forms\Components\Hidden::make('user_id')
                            ->required()
                            ->default(Auth::id()),
                    ])->columns(2)
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('first_name')
                    ->label((__('models/customer.prop.first_name')))
                    ->searchable(),
                Tables\Columns\TextColumn::make('last_name')
                    ->label((__('models/customer.prop.last_name')))
                    ->searchable(),
                Tables\Columns\TextColumn::make('phones.phone')
                    ->label((__('models/customer.prop.phones')))
                    ->color('info')
                    ->verticalAlignment(VerticalAlignment::Start)
                    ->sortable(),
                Tables\Columns\SelectColumn::make('address_id')
                    ->label((__('models/customer.prop.address')))
                    ->options(Address::all()->pluck('name', 'id')->toArray())
                    ->sortable(),
                Tables\Columns\TextColumn::make('date_of_birth')
                    ->label((__('models/customer.prop.date_of_birth')))
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('user_id')
                    ->numeric()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\Filter::make('name')
                    ->form([
                        Forms\Components\TextInput::make('name')
                            ->label('Ism, Familiya'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $name = $data['name'],
                                fn (Builder $query, $name): Builder => $query
                                    ->where('customers.full_name', 'like', "%{$name}%")
                            );
                    }),
                Tables\Filters\Filter::make('phone')
                    ->form([
                        Forms\Components\TextInput::make('phone')
                            ->label(__('models/order.relation.phone'))
                            ->default('+998')
                            ->mask('+999 99 999-99-99')
                            ->tel()
                            ->telRegex('/^[+]*[(]{0,1}[0-9]{1,4}[)]{0,1}[-\s\.\/0-9]*$/'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['phone'] ?? null,
                                function (Builder $query, $name): Builder {
                                    return $query->whereHas('phones', function (Builder $query) use ($name) {
                                        $query->where('phones.phone', 'like', "%{$name}%");
                                    });
                                }
                            );
                    }),
                Tables\Filters\SelectFilter::make('address_id')
                    ->label('Manzil')
                    ->options(Address::all()->pluck('name', 'id')->toArray()),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(3)
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->paginated([50, 100, 200, 'all']);
    }

    public static function getRelations(): array
    {
        return [
            OrdersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListCustomers::route('/'),
            'create' => Pages\CreateCustomer::route('/create'),
            'edit' => Pages\EditCustomer::route('/{record}/edit'),
        ];
    }
}
