<?php

namespace App\Filament\Resources;

use App\Filament\Resources\OrderResource\Pages;
use App\Filament\Resources\OrderResource\RelationManagers;
use App\Filament\Resources\OrderResource\RelationManagers\AddressRelationManager;
use App\Models\Order;
use App\Models\Product;
use Faker\Core\Number;
use Filament\Forms;
use Filament\Forms\Components\Group;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Set;
use Illuminate\Support\Facades\Log;
// use Illuminate\Container\Attributes\Log;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Components\ToggleButtons;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Support\Number\currency;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\SelectColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class OrderResource extends Resource
{
    protected static ?string $model = Order::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-cart';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Group::make()->schema([
                    Section::make('Order Informaiton')->schema([
                        Select::make('user_id')
                            ->label('Customer')
                            ->relationship('user', 'name')  // Ensure it's linking the user relationship
                            ->searchable()
                            ->preload()
                            ->required(),


                        Select::make('payment_method')
                            ->options([
                                'stripe' => 'stripe',
                                'cod' => 'Cash On Delivery'
                            ])
                            ->required(),


                        Select::make('payment_status')
                            ->options([
                                'pending' => 'Pending',
                                'paid' => 'Paid',
                                'failed' => 'Failed',
                            ])
                            ->default('pending')
                            ->dehydrated()
                            ->required(),


                        ToggleButtons::make('status')
                            ->inline()
                            ->default('new')
                            ->options([
                                'new' => 'new',
                                'processing' => 'Processing',
                                'shipped' => 'Shipped',
                                'delivered' => 'Delivered',
                                'canceled' => 'Canceled',
                            ])
                            ->colors([
                                'new' => 'info',
                                'processing' => 'warning',
                                'shipped' => 'info',
                                'delivered' => 'success',
                                'cancelled' => 'danger',
                            ])
                            ->icons([
                                'new' => 'heroicon-m-sparkles',
                                'processing' => 'heroicon-m-arrow-path',
                                'shipped' => 'heroicon-m-truck',
                                'deliverd' => 'heroicon-m-check-badge',
                                'cancelled' => 'heroicon-m-x-circle',
                            ]),

                        Select::make('currency')
                            ->options([
                                'inr' => 'INR',
                                'usd' => 'USD',
                                'eur' => 'EUR',
                                'gdp' => 'GDP',

                            ])
                            ->default('inr')
                            ->required(),

                        Select::make('shipping_method')
                            ->options([
                                'fedex' => 'Fedex',
                                'ups' => 'UPS',
                                'dhl' => 'DHL',
                                'usps' => 'USPS',
                            ]),

                        Textarea::make('notes')
                            ->columnSpanFull()
                    ])->columns(2),


                    Section::make('Order Items')
                        ->schema([
                            Repeater::make('items')
                                ->relationship()
                                ->schema([

                                    Select::make('product_id')
                                        ->relationship('product', 'name')
                                        ->searchable()
                                        ->preload()
                                        ->required()
                                        ->distinct()
                                        ->disableOptionsWhenSelectedInSiblingRepeaterItems()
                                        ->reactive()
                                        ->afterStateUpdated(function ($state, Set $set) {
                                            Log::info('Product ID Selected:', ['product_id' => $state]); // Log the selected product_id
                                            $product = Product::find($state);
                                            $set('unit_amount', $product?->price ?? 0);
                                            $set('total_amount', $product?->price ?? 0);
                                        })
                                        ->columnSpan(3)
                                        ->dehydrated(true),



                                    TextInput::make('quantity')
                                        ->numeric()
                                        ->required()
                                        ->default(1)
                                        ->minValue(1)
                                        ->reactive()
                                        ->afterStateUpdated(fn($state, Set $set, Get $get) => $set('total_amount', $state * $get('unit_amount')))
                                        ->columnSpan(3),


                                    TextInput::make('unit_amount')
                                        ->numeric()
                                        ->required()
                                        ->disabled()
                                        ->dehydrated()
                                        ->columnSpan(3),


                                    TextInput::make('total_amount')
                                        ->numeric()
                                        ->required()
                                        ->disabled()
                                        ->dehydrated()
                                        ->columnSpan(3),


                                ])->columns(12),

                            Placeholder::make('grand_total_placeholders')
                                ->label('Grand Total')
                                ->content(function (Get $get, Set $set) {
                                    $total = 0;

                                    if (!$repeaters = $get('items')) {
                                        return '₹0.00'; // Default if no items are found
                                    } else {
                                        foreach ($repeaters as $key => $repeater) {
                                            $total += $get("items.{$key}.total_amount");
                                        }
                                        $set('grand_total', $total);  // Set the 'grand_total' field explicitly
                                        return '₹' . number_format($total, 2); // For INR with two decimal points
                                    }
                                }),

                            Hidden::make('grand_total')
                                ->default(0)  // Set default value
                                ->dehydrated(true),  // Ensure the value is included when submitting the form

                        ])

                ])->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Customer')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('grand_total')
                    ->label('Customer')
                    ->numeric()
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_method')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('payment_status')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('currency')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('shipping_method')
                    ->sortable()
                    ->searchable(),

                SelectColumn::make('status')
                    ->options([
                        'new' => 'new',
                        'processing' => 'Processing',
                        'shipped' => 'Shipped',
                        'delivered' => 'Delivered',
                        'canceled' => 'Canceled',
                    ])
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('notes')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('updated_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\ViewAction::make(),
                    Tables\Actions\DeleteAction::make(),
                ])
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
            AddressRelationManager::class
        ];
    }

    public static function getNavigationBadge() : ? String {
        return static::getModel()::count();
    }

    public static function getNavigationBadgeColor() : String|Array|null {
        return static::getModel()::count() > 10 ? 'success' : 'danger';
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListOrders::route('/'),
            'create' => Pages\CreateOrder::route('/create'),
            'view' => Pages\ViewOrder::route('/{record}'),
            'edit' => Pages\EditOrder::route('/{record}/edit'),
        ];
    }
}
