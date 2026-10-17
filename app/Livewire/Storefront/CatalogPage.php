<?php

namespace App\Livewire\Storefront;

use App\Livewire\Storefront\Concerns\InteractsWithStore;
use App\Models\Category;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Url;
use Livewire\Component;

#[Layout('components.layouts.app')]
class CatalogPage extends Component
{
    use InteractsWithStore;

    #[Url(as: 'category', except: 'Каталог')]
    public string $selectedCategory = 'Каталог';

    #[Url(as: 'brand', except: 'ALL')]
    public string $selectedBrand = 'ALL';

    #[Url(as: 'q', except: '')]
    public string $searchQuery = '';

    #[Url(as: 'sort', except: 'recommended')]
    public string $sortBy = 'recommended';

    public int $currentPage = 1;

    public int $pageSize = 18;

    public function mount(): void
    {
        if (request()->filled('search') && $this->searchQuery === '') {
            $this->searchQuery = (string) request('search');
        }
    }

    public function selectCategory(string $cat): void
    {
        $this->selectedCategory = $cat;
        $this->currentPage = 1;
    }

    public function updatedSelectedBrand(): void
    {
        $this->currentPage = 1;
    }

    public function updatedSearchQuery(): void
    {
        $this->currentPage = 1;
    }

    public function updatedSortBy(): void
    {
        $this->currentPage = 1;
    }

    public function updatedPageSize(): void
    {
        $this->currentPage = 1;
    }

    public function resetFilters(): void
    {
        $this->selectedCategory = 'Каталог';
        $this->selectedBrand = 'ALL';
        $this->searchQuery = '';
        $this->sortBy = 'recommended';
        $this->currentPage = 1;
    }

    public function setPage(int $page): void
    {
        $this->currentPage = max(1, $page);
    }

    public function render()
    {
        $store = $this->store();
        $allProducts = $store->getAllProducts();

        $brands = array_values(array_unique(array_filter(array_map(
            fn ($p) => is_array($p['brand'] ?? null) ? ($p['brand']['name'] ?? '') : ($p['brand'] ?? ''),
            $allProducts
        ))));
        sort($brands);

        $filtered = $allProducts;

        $cat = trim($this->selectedCategory);
        if ($cat !== '' && ! in_array($cat, ['Каталог', 'Katalog', 'Catalog', 'Все товары', 'Barcha mahsulotlar', 'All Products', 'Бренды', 'Brendlar', 'Brands'], true)) {
            if (in_array($cat, ['Скидки', 'Chegirmalar', 'Discounts'], true)) {
                $filtered = array_values(array_filter($filtered, fn ($p) => ! empty($p['oldPrice']) && $p['oldPrice'] > $p['price']));
            } elseif (in_array($cat, ['Наборы', "To'plamlar", 'Sets'], true)) {
                $filtered = array_values(array_filter($filtered, function ($p) {
                    $title = mb_strtolower($p['title'] ?? '');

                    return ($p['category'] ?? '') === 'Наборы' || str_contains($title, 'nabor') || str_contains($title, 'set') || str_contains($title, 'набор');
                }));
            } else {
                $catLower = mb_strtolower($cat);
                $synonyms = [$catLower];

                $staticGroups = [
                    ['макияж', 'makiyaj', 'makeup'],
                    ['уход', 'parvarish', 'skincare'],
                    ['парфюмерия', 'parfyumeriya', 'fragrance'],
                    ['аксессуары', 'aksessuarlar', 'accessories'],
                ];
                foreach ($staticGroups as $group) {
                    if (in_array($catLower, $group, true)) {
                        $synonyms = array_merge($synonyms, $group);
                    }
                }

                $dbCat = Category::whereRaw('LOWER(name_ru) = ? OR LOWER(name_uz) = ? OR LOWER(name_en) = ?', [$catLower, $catLower, $catLower])->first();
                if ($dbCat) {
                    foreach (['name_ru', 'name_uz', 'name_en'] as $col) {
                        if (! empty($dbCat->{$col})) {
                            $synonyms[] = mb_strtolower((string) $dbCat->{$col});
                        }
                    }
                }
                $synonyms = array_values(array_unique($synonyms));

                $filtered = array_values(array_filter($filtered, function ($p) use ($synonyms) {
                    return in_array(mb_strtolower($p['category'] ?? ''), $synonyms, true)
                        || in_array(mb_strtolower($p['category_en'] ?? ''), $synonyms, true)
                        || in_array(mb_strtolower($p['category_uz'] ?? ''), $synonyms, true);
                }));
            }
        }

        if ($this->selectedBrand !== 'ALL' && $this->selectedBrand !== '') {
            $brandLower = mb_strtolower($this->selectedBrand);
            $filtered = array_values(array_filter($filtered, fn ($p) => mb_strtolower((string) ($p['brand'] ?? '')) === $brandLower));
        }

        // Всегда ищем по артикулу (SKU) + названию (Title) + бренду
        $q = mb_strtolower(trim($this->searchQuery));
        if ($q !== '') {
            $filtered = array_values(array_filter($filtered, function ($p) use ($q) {
                return str_contains(mb_strtolower((string) ($p['sku'] ?? '')), $q)
                    || str_contains(mb_strtolower((string) ($p['title'] ?? '')), $q)
                    || str_contains(mb_strtolower((string) ($p['brand'] ?? '')), $q);
            }));
        }

        if ($this->sortBy === 'price_asc') {
            usort($filtered, fn ($a, $b) => ($a['price'] ?? 0) <=> ($b['price'] ?? 0));
        } elseif ($this->sortBy === 'price_desc') {
            usort($filtered, fn ($a, $b) => ($b['price'] ?? 0) <=> ($a['price'] ?? 0));
        } elseif ($this->sortBy === 'newest') {
            usort($filtered, fn ($a, $b) => ((int) ($b['isNew'] ?? 0)) <=> ((int) ($a['isNew'] ?? 0)));
        }

        $totalCount = count($filtered);
        $totalPages = max(1, (int) ceil($totalCount / max(1, $this->pageSize)));
        $safePage = min($this->currentPage, $totalPages);
        $offset = ($safePage - 1) * $this->pageSize;
        $paginatedProducts = array_slice($filtered, $offset, $this->pageSize);

        return view('livewire.storefront.catalog-page', array_merge($this->sharedViewData(), [
            'brands' => $brands,
            'totalProductsCount' => $totalCount,
            'paginatedProducts' => $paginatedProducts,
            'totalPages' => $totalPages,
            'safePage' => $safePage,
        ]));
    }
}
