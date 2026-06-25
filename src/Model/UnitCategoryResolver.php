<?php

declare(strict_types=1);

namespace SR\BasePriceLinkedProduct\Model;

/**
 * Maps an admin-scope unit option label (e.g. "kg") to a translatable category label
 * (e.g. "Weight").
 *
 * Map keys must be the admin-scope (store_id=0) option labels, always in lowercase.
 * The resolved category label is wrapped in __() so Magento's translation layer applies.
 *
 * Map and fallback are injectable via di.xml so any module can extend or override entries.
 */
class UnitCategoryResolver
{
    /**
     * @param array<string, string> $unitCategoryMap  Lowercase admin label → translation key
     * @param string                $fallbackLabel     Used when no map entry matches; also translatable
     */
    public function __construct(
        private readonly array $unitCategoryMap = [],
        private readonly string $fallbackLabel = 'Units',
    ) {}

    /**
     * Resolve the category label for the given admin-scope unit label.
     *
     * @param string $adminUnitLabel The option label as stored at store_id=0 (e.g. "kg", "ml")
     * @return string Translated category label (e.g. "Weight", "Volume", or the fallback)
     */
    public function resolve(string $adminUnitLabel): string
    {
        $category = $this->unitCategoryMap[strtolower($adminUnitLabel)] ?? $this->fallbackLabel;
        return (string)__($category);
    }
}
