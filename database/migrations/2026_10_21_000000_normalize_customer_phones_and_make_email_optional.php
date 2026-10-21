<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * - customers.email becomes optional; the made-up addresses the website
     *   and the Telegram API stored (client_…@mally.uz, tg_…@mally.uz) become
     *   null. Real emails stay unique.
     * - customers.phone_number is stored in one format and becomes unique, so
     *   one person can't end up as two customers.
     *
     * If two customers have the same number after formatting, nothing is
     * changed and the migration stops with a list of them: merge or fix those
     * customers in the admin, then run it again.
     */
    public function up(): void
    {
        $changes = [];
        $idsByPhone = [];

        foreach (DB::table('customers')->select('id', 'email', 'phone_number')->lazyById(500) as $customer) {
            $phone = $this->normalizePhone($customer->phone_number);
            if ($phone !== null) {
                $idsByPhone[$phone][] = $customer->id;
            }

            $change = [];
            if ($phone !== $customer->phone_number) {
                $change['phone_number'] = $phone;
            }
            if (preg_match('/^(client|tg)_[a-z0-9]{12}@mally\.uz$/', (string) $customer->email)) {
                $change['email'] = null;
            }
            if ($change) {
                $changes[$customer->id] = $change;
            }
        }

        $duplicates = array_filter($idsByPhone, fn (array $ids) => count($ids) > 1);
        if ($duplicates) {
            $list = collect($duplicates)
                ->map(fn (array $ids, string $phone) => $phone.' (customers #'.implode(', #', $ids).')')
                ->implode('; ');

            throw new RuntimeException("These customers share a phone number: {$list}. Merge or fix them in the admin, then run the migration again.");
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->string('email')->nullable()->change();
        });

        foreach ($changes as $id => $change) {
            DB::table('customers')->where('id', $id)->update($change);
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->unique('phone_number');
        });
    }

    /**
     * Phone numbers keep their new format; customers without an email get a
     * made-up one again, because the column becomes required.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->dropUnique(['phone_number']);
        });

        foreach (DB::table('customers')->whereNull('email')->select('id')->lazyById(500) as $customer) {
            DB::table('customers')->where('id', $customer->id)->update([
                'email' => 'client_'.Str::lower(Str::random(12)).'@mally.uz',
            ]);
        }

        Schema::table('customers', function (Blueprint $table) {
            $table->string('email')->nullable(false)->change();
        });
    }

    /** Same rule as Customer::normalizePhone(), copied so this migration never changes. */
    private function normalizePhone(?string $phone): ?string
    {
        $phone = trim((string) $phone);
        if ($phone === '') {
            return null;
        }

        $digits = preg_replace('/\D/', '', $phone);
        if (strlen($digits) === 9) {
            $digits = '998'.$digits;
        }

        if (strlen($digits) !== 12 || ! str_starts_with($digits, '998')) {
            return $phone;
        }

        $s = substr($digits, 3);

        return sprintf('+998 %s %s %s %s', substr($s, 0, 2), substr($s, 2, 3), substr($s, 5, 2), substr($s, 7, 2));
    }
};
