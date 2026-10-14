<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * An order could not be placed for a reason the customer may see
 * (product gone, not enough stock…). The message is already translated and
 * safe to show; any other exception is reported and replaced with a
 * generic message.
 */
class CheckoutException extends RuntimeException {}
