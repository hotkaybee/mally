<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Models\Customer;
use Closure;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;
use Illuminate\Database\Eloquent\Builder;

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
                    ->unique(ignoreRecord: true),
                TextInput::make('phone_number')
                    ->label(__('phone_number'))
                    ->tel()
                    // The same number typed in another format is still taken.
                    ->rules([
                        fn (?Customer $record): Closure => function (string $attribute, mixed $value, Closure $fail) use ($record) {
                            $taken = filled($value) && Customer::wherePhone((string) $value)
                                ->when($record, fn (Builder $query) => $query->whereKeyNot($record->getKey()))
                                ->exists();

                            if ($taken) {
                                $fail(__('A customer with this phone number already exists.'));
                            }
                        },
                    ]),
                TextInput::make('city')
                    ->label(__('city')),
                Textarea::make('address')
                    ->label(__('address'))
                    ->rows(2),
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
