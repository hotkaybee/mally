<?php

namespace App\Filament\Resources\Customers\Schemas;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

class CustomerForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextInput::make('first_name')
                    ->required(),
                TextInput::make('last_name')
                    ->required(),
                TextInput::make('email')
                    ->label('Email address')
                    ->email()
                    ->required(),
                TextInput::make('phone_number')
                    ->tel(),
                TextInput::make('telegram'),
                TextInput::make('instagram'),
                Select::make('source')
                    ->options(['word_of_mouth' => 'Word of mouth', 'friend' => 'Friend', 'instagram' => 'Instagram'])
                    ->required(),
            ]);
    }
}
