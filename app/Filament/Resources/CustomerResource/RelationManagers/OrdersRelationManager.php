<?php

namespace App\Filament\Resources\CustomerResource\RelationManagers;

use App\Filament\Resources\OrderResource\Pages\EditOrder;
use App\Models\Enums\OrderStatus;
use App\Models\Price;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\HtmlString;

class OrdersRelationManager extends RelationManager
{
    protected static string $relationship = 'orders';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('total_price')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->heading('Buyurtmalar')
            ->columns([
                Tables\Columns\TextColumn::make('customer.full_name')
                    ->label(__('models/order.prop.customer'))
                    ->wrap()
                    ->sortable(),
                Tables\Columns\TextColumn::make('doors_count')
                    ->label(__('models/order.prop.doors_count'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('windows_count')
                    ->label(__('models/order.prop.windows_count'))
                    ->sortable(),
                Tables\Columns\TextColumn::make('total_price')
                    ->label(__('models/order.prop.total_price'))
                    ->formatStateUsing(fn ($state) => formatPrice($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Qarz')
                    ->color(function ($record) {
                        $payments = $record->prices->sum('price');
                        $amount = $record->total_price - $payments;
                        if ($amount > 0) {
                            return 'danger';
                        }
                        return 'success';
                    })
                    ->state(function ($record) {
                        $payments = $record->prices->sum('price');
                        $amount = $record->total_price - $payments;
                        if ($amount <= 0) return "To'landi!";
                        return formatPrice($amount);
                    })
                    ->action(
                        Action::make('form')
                            ->label(__('models/order.prop.addable_payment'))
                            ->form([
                                Forms\Components\Placeholder::make('created_at')
                                    ->label(false)
                                    ->content(function ($record) {
                                        $payments = $record->prices->sum('price');
                                        $amount = $record->total_price - $payments;
                                        if ($amount <= 0) return new HtmlString('<em style="color: lime">To\'lov qilingan! Qarzdorlik mavjud emas</em>');
                                        return new HtmlString('<em style="color: red">' . htmlspecialchars(formatPrice($amount)) . ' so\'m qarzdorlik mavjud.</em>');
                                    }),
                                Forms\Components\TextInput::make('price')
                                    ->label(__('models/order.relation.price'))
                                    ->mask(RawJs::make('$money($input, \',\', \' \')'))
                                    ->stripCharacters([',', '.', ' ',])
                                    ->numeric()
                                    ->required(),
                                Forms\Components\DatePicker::make('date')
                                    ->label(__('models/order.prop.date'))
                                    ->native(false)
                                    ->suffixIcon('heroicon-o-calendar-days')
                                    ->placeholder(now()->format('d.m.Y'))
                                    ->displayFormat('d.m.Y')
                                    ->default(now()),
                            ])
                            ->action(function (array $data, $record) {
                                return Price::create([
                                    'order_id' => $record->id,
                                    'price' => $data['price'],
                                    'date' => $data['date'],
                                ]);
                            })->modalWidth('xl')
                    ),
                Tables\Columns\TextColumn::make('date')
                    ->label(__('models/order.prop.date'))
                    ->date()
                    ->formatStateUsing(fn ($state) => formatDateHuman($state))
                    ->sortable(),
                Tables\Columns\TextColumn::make('payment_deadline')
                    ->label(__('models/order.prop.payment_deadline'))
                    ->date()
                    ->formatStateUsing(fn ($state) => formatDateHuman($state))
                    ->sortable(),
                Tables\Columns\SelectColumn::make('status')
                    ->label(__('models/order.prop.status'))
                    ->options(OrderStatus::class)
                    ->sortable(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->hidden(),
            ])
            ->actions([
                Tables\Actions\EditAction::make()
                    ->url(fn ($record) => EditOrder::getUrl(['record' => $record])),
                Tables\Actions\DeleteAction::make()
                    ->hidden(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->hidden(),
                ]),
            ])
            ->paginated([50, 100, 200, 'all']);
    }
}
