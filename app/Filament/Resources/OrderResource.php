<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Models\Customer;
use App\Models\Enums\OrderStatus;
use App\Models\Order;
use App\Models\Price;
use App\Models\ProductType;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Support\RawJs;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Enums\FiltersLayout;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\HtmlString;
use Laravel\SerializableClosure\Serializers\Native;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function getModelLabel(): string
    {
        return __('models/order.singular');
    }

    public static function getPluralModelLabel(): string
    {
        return __('models/order.plural');
    }

    public static function form(Form $form): Form
    {
        $counter = 0;

        return $form
            ->schema([
                Forms\Components\Section::make()
                    ->schema([
                        Forms\Components\Select::make('customer_id')
                            ->label(__('models/order.prop.customer'))
                            ->relationship('customer', 'full_name')
                            ->searchable()
                            ->options(Customer::all()->pluck('full_name', 'id')->toArray())
                            ->createOptionForm(fn (Form $form) => CustomerResource::form($form))
                            ->required(),
                        Forms\Components\Select::make('productTypes')
                            ->label(__('models/order.prop.product_types'))
                            ->relationship('productTypes', 'name')
                            ->options(ProductType::all()->pluck('name', 'id')->toArray())
                            ->multiple(),
                        Forms\Components\TextInput::make('total_price')
                            ->label(__('models/order.prop.total_price'))
                            ->mask(RawJs::make('$money($input, \',\', \' \')'))
                            ->stripCharacters([',', '.', ' ',])
                            ->numeric(),
                        Forms\Components\Repeater::make('prices')
                            ->label(__('models/order.prop.prices'))
                            ->relationship('prices')
                            ->addActionLabel(__('models/order.prop.addable_payment'))
                            ->schema([
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
                            ->columns(2),
                        Forms\Components\Textarea::make('note')
                            ->label(__('models/order.prop.note'))
                            ->rows(4)
                            ->columnSpanFull(),
                    ])->columnSpan(2),
                Forms\Components\Section::make(__('models/order.prop.settings'))
                    ->schema([
                        Forms\Components\DatePicker::make('date')
                            ->label(__('models/order.prop.date'))
                            ->native(false)
                            ->suffixIcon('heroicon-o-calendar-days')
                            ->placeholder(now()->format('d.m.Y'))
                            ->displayFormat('d.m.Y')
                            ->default(now()),
                        Forms\Components\DatePicker::make('payment_deadline')
                            ->label(__('models/order.prop.payment_deadline'))
                            ->native(false)
                            ->suffixIcon('heroicon-o-calendar-days')
                            ->placeholder(now()->format('d.m.Y'))
                            ->displayFormat('d.m.Y'),
                        Forms\Components\TextInput::make('doors_count')
                            ->label(__('models/order.prop.doors_count'))
                            ->numeric()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('windows_count')
                            ->label(__('models/order.prop.windows_count'))
                            ->numeric()
                            ->maxLength(255),
                        Forms\Components\ToggleButtons::make('status')
                            ->label(__('models/order.prop.status'))
                            ->options(OrderStatus::class)
                            ->inline()
                            ->colors([
                                '1' => 'danger',
                                '2' => 'warning',
                                '0' => 'success',
                            ])
                            ->default(OrderStatus::ACTIVE),
                        Forms\Components\Hidden::make('user_id')
                            ->default(Auth::id()),
                    ])->columnSpan(1),
            ])->columns(3);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->query(
                Order::query()->latest('date')
            )
            ->columns([
                Tables\Columns\TextColumn::make('customer.full_name')
                    ->label(__('models/order.prop.customer'))
                    ->wrap()
                    ->sortable(),
                Tables\Columns\TextColumn::make('customer.phones.phone')
                    ->label(__('models/order.relation.phone'))
                    ->wrap()
                    ->color('info')
                    ->copyable()
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
                                    ->whereHas('customer', function (Builder $query) use ($name) {
                                        $query->where('customers.full_name', 'like', "%{$name}%");
                                    })
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
                                    return $query->whereHas('customer', function (Builder $query) use ($name) {
                                        $query->whereHas('phones', function (Builder $query) use ($name) {
                                            $query->where('phones.phone', 'like', "%{$name}%");
                                        });
                                    });
                                }
                            );
                    }),
                Tables\Filters\TernaryFilter::make('total_price')
                    ->label('To\'lov holati')
                    ->placeholder('Barchasi')
                    ->trueLabel('Qarzdorlar')
                    ->falseLabel('To\'lab bo\'lganlar')
                    ->queries(
                        true: function (Builder $query) {
                            $query->whereExists(function ($subQuery) {
                                $subQuery->select(DB::raw('SUM(price)'))
                                    ->from('prices')
                                    ->whereColumn('prices.order_id', 'orders.id')
                                    ->havingRaw('SUM(price) < orders.total_price');
                            });
                        },
                        false: function (Builder $query) {
                            $query->whereExists(function ($subQuery) {
                                $subQuery->select(DB::raw('SUM(price)'))
                                    ->from('prices')
                                    ->whereColumn('prices.order_id', 'orders.id')
                                    ->havingRaw('SUM(price) >= orders.total_price');
                            });
                        },
                        blank: function (Builder $query) {
                            $query;
                        },
                    ),
                Tables\Filters\Filter::make('date')
                    ->form([
                        Forms\Components\DatePicker::make('date')
                            ->suffixIcon('heroicon-o-calendar-days')
                            ->placeholder(now()->format('d.m.Y'))
                            ->label('Buyurtma olingan sana')
                            ->native(false)
                            ->displayFormat('d F Y'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['date'],
                                fn (Builder $query, $date): Builder => $query->whereDate('date', '=', $date),
                            );
                    }),
                Tables\Filters\Filter::make('payment_deadline')
                    ->form([
                        Forms\Components\DatePicker::make('payment_deadline')
                            ->label('To\'lov muddati')
                            ->native(false)->suffixIcon('heroicon-o-calendar-days')
                            ->placeholder(now()->format('d.m.Y'))
                            ->displayFormat('d F Y'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        return $query
                            ->when(
                                $data['payment_deadline'],
                                fn (Builder $query, $date): Builder => $query->whereDate('payment_deadline', '=', $date),
                            );
                    }),
                Tables\Filters\SelectFilter::make('status')
                    ->label('Buyurtma holati')
                    ->options(OrderStatus::class),
            ], layout: FiltersLayout::AboveContent)
            ->filtersFormColumns(6)
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make()
                        ->hidden(),
                ]),
            ])
            ->paginated([50, 100, 200, 300]);
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
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
