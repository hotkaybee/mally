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
                    ->label(__('first_name'))
                    ->required(),
                TextInput::make('last_name')
                    ->label(__('last_name'))
                    ->required(),
                TextInput::make('email')
                    ->label(__('Email address'))
                    ->email()
                    ->required(),
                TextInput::make('phone_number')
                    ->label(__('phone_number'))
                    ->tel(),
                TextInput::make('telegram')
                    ->label(__('telegram')),
                TextInput::make('instagram')
                    ->label(__('instagram')),
                Select::make('source')
                    ->label(__('source'))
                    ->options([
                        'word_of_mouth' => __('Word of mouth'),
                        'friend' => __('Friend'),
                        'instagram' => __('Instagram'),
                        'telegram' => __('Telegram'),
                        'website' => __('Website'),
                    ])
                    ->required(),
            ]);
    }
}
