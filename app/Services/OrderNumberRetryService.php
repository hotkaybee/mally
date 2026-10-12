<?php

namespace App\Services;

use App\Models\Order;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

class OrderNumberRetryService
{
    public const MAX_ATTEMPTS = 3;

    /**
     * Execute the given order-creation callback inside a database transaction,
     * retrying up to $maxAttempts total attempts only when a QueryException is
     * caused by a duplicate/unique collision on `orders.order_number`.
     *
     * @template TReturn
     *
     * @param  callable(int): TReturn  $operation
     * @return TReturn
     */
    public function run(callable $operation, int $maxAttempts = self::MAX_ATTEMPTS): mixed
    {
        $maxAttempts = max(1, $maxAttempts);
        $attempt = 0;

        while (true) {
            $attempt++;

            try {
                return DB::transaction(fn () => $operation($this->nextOrderNumber(), $attempt));
            } catch (QueryException $e) {
                if ($attempt >= $maxAttempts || ! $this->isDuplicateOrderNumberException($e)) {
                    throw $e;
                }
            }
        }
    }

    /**
     * Alias for run().
     *
     * @template TReturn
     *
     * @param  callable(int): TReturn  $operation
     * @return TReturn
     */
    public function execute(callable $operation, int $maxAttempts = self::MAX_ATTEMPTS): mixed
    {
        return $this->run($operation, $maxAttempts);
    }

    public function nextOrderNumber(): int
    {
        $maxNum = (int) (Order::max('order_number') ?: 1000);

        return $maxNum + 1;
    }

    public function isDuplicateOrderNumberException(QueryException $e): bool
    {
        $sqlState = (string) ($e->errorInfo[0] ?? $e->getCode());
        if (! in_array($sqlState, ['23000', '23505'], true)) {
            return false;
        }

        $rawMessage = (string) ($e->errorInfo[2] ?? $e->getPrevious()?->getMessage() ?? $e->getMessage());
        $driverMessage = strtolower((string) preg_replace('/\s*\(connection:.*$/is', '', $rawMessage));

        $isUniqueCollision = str_contains($driverMessage, 'unique')
            || str_contains($driverMessage, 'duplicate');

        $targetsOrderNumber = str_contains($driverMessage, 'orders.order_number')
            || str_contains($driverMessage, 'orders_order_number_unique')
            || str_contains($driverMessage, 'order_number');

        return $isUniqueCollision && $targetsOrderNumber;
    }
}
