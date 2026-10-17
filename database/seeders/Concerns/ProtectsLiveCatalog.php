<?php

namespace Database\Seeders\Concerns;

use App\Models\Order;
use App\Models\Product;

/**
 * The catalog seeders start by deleting brands, categories, products and
 * receipts. Outside local/testing they only run on an empty shop, so
 * RUN_SEEDERS=true or `db:seed --force` can't wipe a live catalog.
 */
trait ProtectsLiveCatalog
{
    protected function wouldWipeLiveCatalog(): bool
    {
        if (app()->environment('local', 'testing') || (! Product::exists() && ! Order::exists())) {
            return false;
        }

        $this->command?->warn(static::class.' skipped: the shop already has products or orders and seeding would delete them.');

        return true;
    }
}
