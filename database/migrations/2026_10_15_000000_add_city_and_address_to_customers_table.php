<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * The storefront used to save "City | address" into customers.instagram.
     * Give them real columns and move only those storefront-written values
     * (customers with the placeholder storefront emails); admin-entered
     * Instagram handles are left untouched.
     */
    public function up(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            $table->string('city')->nullable()->after('phone_number');
            $table->text('address')->nullable()->after('city');
        });

        $this->storefrontCustomers()
            ->whereNotNull('instagram')
            ->orderBy('id')
            ->chunkById(200, function ($customers) {
                foreach ($customers as $customer) {
                    [$city, $address] = array_pad(array_map('trim', explode('|', $customer->instagram, 2)), 2, null);

                    DB::table('customers')->where('id', $customer->id)->update([
                        'city' => $city ?: null,
                        'address' => $address ?: null,
                        'instagram' => null,
                    ]);
                }
            });
    }

    public function down(): void
    {
        $this->storefrontCustomers()
            ->whereNull('instagram')
            ->where(fn ($q) => $q->whereNotNull('city')->orWhereNotNull('address'))
            ->orderBy('id')
            ->chunkById(200, function ($customers) {
                foreach ($customers as $customer) {
                    DB::table('customers')->where('id', $customer->id)->update([
                        'instagram' => trim(($customer->city ?? '').($customer->address ? ' | '.$customer->address : '')),
                    ]);
                }
            });

        Schema::table('customers', function (Blueprint $table) {
            $table->dropColumn(['city', 'address']);
        });
    }

    private function storefrontCustomers()
    {
        return DB::table('customers')->where(function ($q) {
            $q->where('email', 'like', 'client_%@mally.uz')
                ->orWhere('email', 'like', 'tg_%@mally.uz');
        });
    }
};
