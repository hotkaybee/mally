<?php

namespace App\Filament\Resources\Customers\Schemas;

use App\Models\Customer;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;

class CustomerInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->components([
                TextEntry::make('first_name'),
                TextEntry::make('last_name'),
                self::confirmable(
                    TextEntry::make('email')->label('Email address'),
                    // The badge is about the account's email, so only when it is the one shown here.
                    fn (Customer $record) => $record->user && filled($record->email)
                        && mb_strtolower($record->email) === mb_strtolower((string) $record->user->email)
                        ? $record->emailStatus() : null,
                ),
                self::confirmable(
                    TextEntry::make('phone_number')->placeholder('-'),
                    fn (Customer $record) => $record->user ? $record->phoneStatus() : null,
                ),
                TextEntry::make('city')
                    ->label(__('city'))
                    ->placeholder('-'),
                TextEntry::make('address')
                    ->label(__('address'))
                    ->placeholder('-'),
                TextEntry::make('telegram')
                    ->placeholder('-'),
                TextEntry::make('instagram')
                    ->placeholder('-'),
                TextEntry::make('source')
                    ->badge(),
                TextEntry::make('created_at')
                    ->dateTime()
                    ->placeholder('-'),
                TextEntry::make('updated_at')
                    ->dateTime()
                    ->placeholder('-'),
            ]);
    }

    /**
     * "Confirmed" / "Not confirmed" next to the label, the same as in the
     * customer's profile. Nothing for customers without a website account.
     *
     * @param  callable(Customer): ?string  $status  missing | unverified | verified, or null for no badge
     */
    private static function confirmable(TextEntry $entry, callable $status): TextEntry
    {
        return $entry
            ->hint(fn (Customer $record) => match ($status($record)) {
                'verified' => __('Confirmed'),
                'unverified' => __('Not confirmed'),
                default => null,
            })
            ->hintIcon(fn (Customer $record) => $status($record) === 'verified' ? Heroicon::CheckCircle : null)
            ->hintColor(fn (Customer $record) => $status($record) === 'verified' ? 'success' : 'warning');
    }
}
