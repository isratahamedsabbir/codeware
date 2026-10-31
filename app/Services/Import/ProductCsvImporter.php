<?php

namespace App\Services\Import;

use App\Models\Page;
use App\Models\Product;
use App\Models\ProductBrand;
use App\Models\ProductCategory;
use App\Models\ProductVendor;
use App\Support\Slug;
use Illuminate\Support\Str;

/**
 * Turns the Products screen's CSV back into products.
 *
 * Columns, in the order the export writes them:
 *
 *   ID, Name (EN), Name (BN), Slug, Category, Price, Status, Featured,
 *   Sort Order, Created At, SKU, Quantity, Discount Price, Product Type,
 *   Charge Shipping, Upcoming, Brand, Vendor, Excerpt (EN), Excerpt (BN),
 *   Description (EN), Description (BN)
 *
 * Only Name (EN) is required for a new row. Existing products are found by
 * ID, then slug, then SKU, and are only overwritten when the "update existing"
 * toggle is on — see the import modal on the Products screen.
 */
class ProductCsvImporter extends CsvImporter
{
    protected function requiredColumn(): string
    {
        return 'name en';
    }

    protected function requiredColumnLabel(): string
    {
        return 'Name (EN)';
    }

    /**
     * @param  array<string, string>  $row
     * @return array{status: 'created'|'updated'|'skipped', warnings?: list<string>}
     */
    protected function importRow(array $row, bool $updateExisting, int $userId): array
    {
        $nameEn = $this->value($row, 'name en');

        if ($nameEn === '') {
            throw new \RuntimeException('Name (EN) is required.');
        }

        $existing = $this->findExisting($row);

        if ($existing && ! $updateExisting) {
            return ['status' => 'skipped'];
        }

        $creating = ! $existing;
        $warnings = [];

        $data = [
            'name' => $this->translations($row, 'name en', 'name bn') ?: ['en' => $nameEn],
        ];

        if ($this->has($row, 'price') && ! $this->empty($row, 'price')) {
            $data['price'] = $this->decimal($row, 'price');
        } elseif ($creating) {
            $data['price'] = 0;
        }

        if ($this->has($row, 'discount price')) {
            $discount = $this->empty($row, 'discount price') ? null : $this->decimal($row, 'discount price');
            $price = (float) ($data['price'] ?? $existing?->price ?? 0);

            // Same rule as Product::hasDiscount(): a sale price only counts
            // when it is actually cheaper than the regular price.
            if ($discount !== null && $discount >= $price) {
                $warnings[] = 'Discount Price was not lower than Price; it was left empty.';
                $discount = null;
            }

            $data['discount_price'] = $discount;
        }

        // A blank cell means "not specified", not "clear it": a hand-made
        // template with empty optional columns must not wipe stock, prices,
        // categories or SKUs off products it is only meant to rename. An
        // explicit 0 (or "No") is how a spreadsheet clears a numeric value.
        if ($this->has($row, 'quantity') && ! $this->empty($row, 'quantity')) {
            $data['quantity'] = $this->integer($row, 'quantity', 0);
        } elseif ($creating) {
            $data['quantity'] = 0;
        }

        if ($this->has($row, 'sort order') && ! $this->empty($row, 'sort order')) {
            $data['sort_order'] = $this->integer($row, 'sort order', 0);
        } elseif ($creating) {
            $data['sort_order'] = $this->nextSortOrder();
        }

        if ($this->has($row, 'sku') && ! $this->empty($row, 'sku')) {
            $data['sku'] = $this->resolveSku($row, $existing);
        }

        if ($this->has($row, 'product type') && ! $this->empty($row, 'product type')) {
            $type = strtolower($this->value($row, 'product type'));
            $data['product_type'] = in_array($type, ['digital', 'physical'], true) ? $type : 'physical';
        } elseif ($creating) {
            $data['product_type'] = 'physical';
        }

        if ($this->has($row, 'featured') && ! $this->empty($row, 'featured')) {
            $data['is_featured'] = $this->boolean($row, 'featured');
        } elseif ($creating) {
            $data['is_featured'] = false;
        }

        if ($this->has($row, 'upcoming') && ! $this->empty($row, 'upcoming')) {
            $data['is_upcoming'] = $this->boolean($row, 'upcoming');
        } elseif ($creating) {
            $data['is_upcoming'] = false;
        }

        if ($this->has($row, 'charge shipping') && ! $this->empty($row, 'charge shipping')) {
            $data['charge_shipping'] = $this->boolean($row, 'charge shipping', true);
        } elseif ($creating) {
            $data['charge_shipping'] = true;
        }

        if ($this->has($row, 'brand') && ! $this->empty($row, 'brand')) {
            $data['brand_id'] = $this->resolveBrand($this->value($row, 'brand'), $warnings);
        }

        if ($this->has($row, 'vendor') && ! $this->empty($row, 'vendor')) {
            $data['vendor_id'] = $this->resolveVendor($this->value($row, 'vendor'), $warnings);
        }

        if ($this->hasAny($row, ['excerpt en', 'excerpt bn'])) {
            $data['excerpt'] = $this->translations($row, 'excerpt en', 'excerpt bn') ?: null;
        }

        if ($this->hasAny($row, ['description en', 'description bn'])) {
            $data['description'] = $this->translations($row, 'description en', 'description bn') ?: null;
        }

        if (($status = $this->status($row, $creating)) !== null) {
            $data['status'] = $status;
        }

        if ($creating) {
            $data['created_by'] = $userId;
        }

        if ($existing) {
            $product = $existing;
            $product->update($data);
        } else {
            $product = Product::create($data);
        }

        if ($this->has($row, 'category') && ! $this->empty($row, 'category')) {
            $product->categories()->sync(
                $this->resolveCategories($this->value($row, 'category'), $warnings)
            );
        }

        $slug = $this->desiredSlug($row, $product, $nameEn);

        Page::updateOrCreate(
            ['type' => 'product', 'product_id' => $product->id],
            [
                'user_id' => $userId,
                'title' => $product->name,
                'slug' => $this->uniqueSlug($slug, $product->page?->id, 'product'),
                'status' => $product->status,
            ]
        );

        return [
            'status' => $creating ? 'created' : 'updated',
            'warnings' => $warnings,
        ];
    }

    /**
     * Finds the row's existing product by ID first, then slug, then SKU.
     *
     * @param  array<string, string>  $row
     */
    protected function findExisting(array $row): ?Product
    {
        $id = (int) $this->value($row, 'id');

        if ($id > 0 && ($product = Product::find($id))) {
            return $product;
        }

        $slug = $this->value($row, 'slug');

        if ($slug !== '') {
            $page = Page::query()
                ->where('type', 'product')
                ->where('slug', Slug::lower($slug))
                ->first();

            if ($page?->product) {
                return $page->product;
            }
        }

        $sku = $this->value($row, 'sku');

        if ($sku !== '') {
            return Product::query()->where('sku', $sku)->first();
        }

        return null;
    }

    /**
     * @param  array<string, string>  $row
     */
    protected function resolveSku(array $row, ?Product $existing): ?string
    {
        $sku = $this->value($row, 'sku');

        if ($sku === '') {
            return null;
        }

        $clash = Product::withTrashed()
            ->where('sku', $sku)
            ->when($existing !== null, fn ($query) => $query->whereKeyNot($existing->id))
            ->exists();

        if ($clash) {
            throw new \RuntimeException('SKU "'.$sku.'" is already used by another product.');
        }

        return $sku;
    }

    /**
     * @param  list<string>  $warnings
     * @return list<int>
     */
    protected function resolveCategories(string $value, array &$warnings): array
    {
        if ($value === '') {
            return [];
        }

        $names = preg_split('/[,;|]+/', $value) ?: [];
        $ids = [];

        foreach ($names as $name) {
            $name = trim($name);

            if ($name === '') {
                continue;
            }

            $category = ProductCategory::query()
                ->where(fn ($query) => $query
                    ->where('name->en', $name)
                    ->orWhere('name->bn', $name))
                ->first();

            if (! $category) {
                $warnings[] = 'Category "'.$name.'" was not found and was skipped.';

                continue;
            }

            $ids[] = $category->id;
        }

        return array_values(array_unique($ids));
    }

    /**
     * @param  list<string>  $warnings
     */
    protected function resolveBrand(string $name, array &$warnings): ?int
    {
        if ($name === '') {
            return null;
        }

        $brand = ProductBrand::query()
            ->where(fn ($query) => $query
                ->where('name->en', $name)
                ->orWhere('name->bn', $name))
            ->first();

        if (! $brand) {
            $warnings[] = 'Brand "'.$name.'" was not found; the product was imported without it.';

            return null;
        }

        return $brand->id;
    }

    /**
     * @param  list<string>  $warnings
     */
    protected function resolveVendor(string $name, array &$warnings): ?int
    {
        if ($name === '') {
            return null;
        }

        $vendor = ProductVendor::query()->where('name', $name)->first();

        if (! $vendor) {
            $warnings[] = 'Vendor "'.$name.'" was not found; the product was imported without it.';

            return null;
        }

        return $vendor->id;
    }

    /**
     * @param  array<string, string>  $row
     */
    protected function desiredSlug(array $row, Product $product, string $nameEn): string
    {
        $slug = $this->value($row, 'slug');

        if ($slug !== '') {
            return $slug;
        }

        return $product->page?->slug ?? Str::slug($nameEn, '-');
    }

    protected function decimal(array $row, string $key): float
    {
        $value = $this->value($row, $key);

        if (! is_numeric($value)) {
            throw new \RuntimeException('"'.$key.'" must be a number: '.$value);
        }

        return (float) $value;
    }

    protected function integer(array $row, string $key, int $default = 0): int
    {
        $value = $this->value($row, $key);

        if (! is_numeric($value)) {
            throw new \RuntimeException('"'.$key.'" must be a whole number: '.$value);
        }

        return max(0, (int) $value);
    }

    /**
     * Imported products with no order value append to the end of the list,
     * just as a product created from the form would.
     */
    protected function nextSortOrder(): int
    {
        return ((int) Product::max('sort_order')) + 1;
    }
}
