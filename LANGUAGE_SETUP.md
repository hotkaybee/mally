# Language Setup Documentation

## Overview
The Mally application now supports **English**, **Russian**, and **Uzbek** languages. Russian is set as the default language.

## How It Works

### 1. Default Language
The application is configured to use Russian (`ru`) as the default language. This is set in `config/app.php`:

```php
'locale' => env('APP_LOCALE', 'ru'),
```

### 2. Translation Files

#### Filament UI Translations
Filament's built-in UI elements (buttons, labels, validation messages, etc.) are automatically translated using the published language files located in:
- `/lang/vendor/filament/en/` - English translations
- `/lang/vendor/filament/ru/` - Russian translations
- `/lang/vendor/filament/uz/` - Uzbek translations

#### Custom Application Translations
Custom labels for your resources, widgets, and forms are translated using JSON translation files:
- `/lang/en.json` - English translations
- `/lang/ru.json` - Russian translations
- `/lang/uz.json` - Uzbek translations

### 3. What's Translated

All the following elements are now translatable:

#### Navigation Menu
- Products → Товары (Russian) / Mahsulotlar (Uzbek)
- Orders → Заказы (Russian) / Buyurtmalar (Uzbek)
- Customers → Клиенты (Russian) / Mijozlar (Uzbek)
- Brands → Бренды (Russian) / Brendlar (Uzbek)
- Categories → Категории (Russian) / Kategoriyalar (Uzbek)

#### Dashboard Widgets
- Sales Overview → Обзор продаж (RU) / Savdo ko'rinishi (UZ)
- Total Orders → Всего заказов (RU) / Jami buyurtmalar (UZ)
- Total Revenue → Общий доход (RU) / Umumiy daromad (UZ)
- Total Customers → Всего клиентов (RU) / Jami mijozlar (UZ)
- Total Products → Всего товаров (RU) / Jami mahsulotlar (UZ)
- Monthly Revenue → Месячный доход (RU) / Oylik daromad (UZ)
- Recent Orders → Последние заказы (RU) / So'nggi buyurtmalar (UZ)
- Low Stock Products → Товары с низким запасом (RU) / Kam qolgan mahsulotlar (UZ)

#### Table Columns
All column headers in tables are translated, including:
- Category, Brand, Name, Quantity, Price, Status, etc.

#### Form Fields
All form field labels are translated, including:
- Input fields, Select dropdowns, Textareas, etc.

#### Status Values
Product and order statuses are translated:
- In stock → В наличии (RU) / Mavjud (UZ)
- Out of stock → Нет в наличии (RU) / Mavjud emas (UZ)
- On the way → В пути (RU) / Yo'lda (UZ)
- Processing → В обработке (RU) / Jarayonda (UZ)
- Awaiting delivery → Ожидает доставки (RU) / Yetkazib berishni kutmoqda (UZ)
- Delivered → Доставлено (RU) / Yetkazildi (UZ)

## Switching Languages

### Method 1: Environment Variable (Recommended)
To change the default language for the entire application, update your `.env` file:

```env
APP_LOCALE=en  # For English
APP_LOCALE=ru  # For Russian (default)
APP_LOCALE=uz  # For Uzbek
```

Then clear the cache:
```bash
php artisan optimize:clear
```

### Method 2: Per-User Language (Future Enhancement)
To allow users to switch languages dynamically, you can:
1. Install a language switcher plugin
2. Store user language preference in the database
3. Set the locale in middleware based on user preference

## Adding New Translations

### For Custom Labels
1. Open `/lang/en.json`, `/lang/ru.json`, and `/lang/uz.json`
2. Add your new translation key-value pairs:

```json
{
    "New Label": "New Label",     // en.json
    "New Label": "Новая метка",   // ru.json
    "New Label": "Yangi yorliq"   // uz.json
}
```

3. Use the translation in your code:
```php
->label(__('New Label'))
```

### For New Languages
1. Check if Filament supports the language in `/lang/vendor/filament/`
2. Create a new JSON file: `/lang/[locale].json` (e.g., `/lang/kk.json` for Kazakh)
3. Copy the structure from `en.json`, `ru.json`, or `uz.json`
4. Translate all values to the new language
5. Update `config/app.php` to use the new locale if needed

## Technical Implementation

### Resources
All resource classes include translation methods:
```php
public static function getNavigationLabel(): string
{
    return __('Products');
}

public static function getModelLabel(): string
{
    return __('Products');
}
```

### Widgets
Widget headings use translation functions:
```php
public function getHeading(): ?string
{
    return __('Monthly Revenue');
}
```

### Tables and Forms
All column and field labels use the `__()` helper:
```php
TextColumn::make('name')
    ->label(__('name'))
    ->searchable(),
```

## Maintenance

When adding new features:
1. Always use `__('key')` for translatable strings
2. Add the translation key to all three files: `en.json`, `ru.json`, and `uz.json`
3. Test in all languages to ensure proper display

## Notes
- Filament automatically handles RTL (Right-to-Left) languages
- Date and number formatting respects the locale settings
- Validation messages are automatically translated by Filament
