<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class OrderNumberMigrationTest extends TestCase
{
    use RefreshDatabase;

    private const MIGRATION = 'database/migrations/2026_10_16_000000_replace_order_number_with_order_id.php';

    public function test_reviews_are_linked_to_existing_orders_and_number_columns_are_dropped(): void
    {
        $this->artisan('migrate:rollback', ['--path' => self::MIGRATION])->assertSuccessful();

        $first = DB::table('orders')->insertGetId(['amount' => 10, 'order_number' => null, 'created_at' => now(), 'updated_at' => now()]);
        $second = DB::table('orders')->insertGetId(['amount' => 20, 'order_number' => null, 'created_at' => now(), 'updated_at' => now()]);
        DB::table('orders')->where('id', $first)->update(['order_number' => 1000 + $first]);
        DB::table('orders')->where('id', $second)->update(['order_number' => 1000 + $second]);

        $review = fn (?int $number) => DB::table('reviews')->insertGetId([
            'order_number' => $number, 'author_name' => 'A', 'comment' => 'ok', 'created_at' => now(), 'updated_at' => now(),
        ]);
        $linked = $review(1000 + $second);
        $orphan = $review(1000 + $second + 50);
        $none = $review(null);

        $this->artisan('migrate', ['--path' => self::MIGRATION])->assertSuccessful();

        $this->assertFalse(Schema::hasColumn('orders', 'order_number'));
        $this->assertFalse(Schema::hasColumn('reviews', 'order_number'));
        $this->assertSame($second, (int) DB::table('reviews')->where('id', $linked)->value('order_id'));
        $this->assertNull(DB::table('reviews')->where('id', $orphan)->value('order_id'));
        $this->assertNull(DB::table('reviews')->where('id', $none)->value('order_id'));
        $this->assertSame(3, DB::table('reviews')->count());
    }
}
