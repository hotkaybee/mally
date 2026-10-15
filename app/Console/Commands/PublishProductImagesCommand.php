<?php

namespace App\Console\Commands;

use App\Models\Product;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

/**
 * Product photos uploaded before the image field used the public disk landed
 * in private storage (storage/app/private), where the storefront can't show
 * them. This copies them to the public disk under the same path; the
 * originals stay where they are.
 */
class PublishProductImagesCommand extends Command
{
    protected $signature = 'products:publish-images';

    protected $description = 'Copy product photos from private storage to the public disk';

    public function handle(): int
    {
        $copied = 0;

        Product::whereNotNull('image')->where('image', '!=', '')->each(function (Product $product) use (&$copied) {
            $path = ltrim((string) $product->image, '/');

            if (preg_match('#^https?://#i', $path) || str_contains($path, '..') || Storage::disk('public')->exists($path)) {
                return;
            }

            if (Storage::disk('local')->exists($path)) {
                Storage::disk('public')->put($path, Storage::disk('local')->get($path));
                $copied++;
            }
        });

        $this->info("Copied {$copied} product image(s) to the public disk.");

        return self::SUCCESS;
    }
}
