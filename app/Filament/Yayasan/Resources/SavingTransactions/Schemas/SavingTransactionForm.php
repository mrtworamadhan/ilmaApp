<?php

namespace App\Filament\Yayasan\Resources\SavingTransactions\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class SavingTransactionForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                Select::make('saving_account_id')
                    ->relationship('savingAccount', 'account_number')
                    ->required(),
                Select::make('type')
                    ->options([
                        'CREDIT' => 'Setor (Kredit)',
                        'DEBIT' => 'Tarik (Debit)',
                    ])
                    ->required(),
                TextInput::make('amount')
                    ->required()
                    ->numeric()
                    ->prefix('Rp'),
                TextInput::make('description')
                    ->maxLength(255),
            ]);
    }
}
