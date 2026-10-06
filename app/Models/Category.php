<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Category extends Model
{
    use HasFactory;

    /**
     * One column per supported locale — the public site will show category
     * names in Uzbek, Russian and English.
     */
    protected $fillable = ['name_en', 'name_ru', 'name_uz', 'icon'];

    /**
     * Expose the current locale's name as `name`, so existing call sites
     * (Filament's `category.name`, `$product->category->name`) keep working
     * and automatically follow the active locale.
     */
    protected $appends = ['name'];

    /**
     * Locales to fall back through when the active locale has no translation,
     * so a half-translated category still renders something useful.
     */
    private const FALLBACK_LOCALES = ['en', 'ru', 'uz'];

    public function getNameAttribute(): string
    {
        foreach ([app()->getLocale(), ...self::FALLBACK_LOCALES] as $locale) {
            $name = $this->attributes['name_'.$locale] ?? null;

            if (filled($name)) {
                return $name;
            }
        }

        return '';
    }

    /**
     * Column holding the name for a locale, for ordering and searching in SQL
     * (e.g. `->orderBy(Category::nameColumn())`).
     */
    public static function nameColumn(?string $locale = null): string
    {
        $locale = $locale ?? app()->getLocale();

        return in_array($locale, self::FALLBACK_LOCALES, true)
            ? 'name_'.$locale
            : 'name_en';
    }

    /**
     * Every locale's name column — handy for searching across all languages.
     *
     * @return array<int, string>
     */
    public static function nameColumns(): array
    {
        return array_map(fn (string $locale) => 'name_'.$locale, self::FALLBACK_LOCALES);
    }
}
