<?php

declare(strict_types=1);

namespace SR\BasePriceLinkedProduct\Model;

use Magento\Catalog\Api\Data\ProductInterface;
use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config as EavConfig;
use SR\SimpleProductLink\Api\VirtualAttributeInterface;

/**
 * Virtual variation attribute that combines baseprice_product_amount and baseprice_product_unit
 * into a single runtime-computed display value (e.g. "3 kg" or "500 ml").
 *
 * - getValueForProduct() uses the frontend-translated unit text so the displayed option
 *   value is in the customer's language (e.g. "3 ק״ג" on a Hebrew store).
 * - getFrontendLabel() uses the admin-scope (store_id=0) unit label for the resolver
 *   map lookup, then wraps the resolved category through __() for translation.
 */
class BasePriceVirtualAttribute implements VirtualAttributeInterface
{
    public const ATTRIBUTE_CODE = 'sr_baseprice_amount_unit';

    private const AMOUNT_ATTRIBUTE = 'baseprice_product_amount';
    private const UNIT_ATTRIBUTE   = 'baseprice_product_unit';

    public function __construct(
        private readonly EavConfig $eavConfig,
        private readonly UnitCategoryResolver $unitCategoryResolver,
    ) {}

    public function getAttributeCode(): string
    {
        return self::ATTRIBUTE_CODE;
    }

    public function getAdminLabel(): string
    {
        return (string)__('Product Amount and Unit');
    }

    /**
     * Returns the axis heading for the storefront (e.g. "Weight", "Volume", "Size").
     *
     * The current product's unit determines the category. The admin-scope option label
     * is used for map lookup to avoid matching translated store labels.
     */
    public function getFrontendLabel(ProductInterface $product): string
    {
        $rawOptionId = $product->getData(self::UNIT_ATTRIBUTE);
        if ($rawOptionId === null || $rawOptionId === '') {
            return (string)__($this->unitCategoryResolver->resolve(''));
        }

        $adminLabel = $this->resolveAdminUnitLabel((string)$rawOptionId);
        return $this->unitCategoryResolver->resolve($adminLabel);
    }

    /**
     * Returns the display value for the product, e.g. "3 kg" or "500 ml".
     * Uses the frontend-translated unit text (getAttributeText) so the value is
     * always rendered in the customer's locale.
     *
     * Returns null when either component is missing — the product is excluded from the group.
     */
    public function getValueForProduct(ProductInterface $product): ?string
    {
        /** @var Product $product */
        $amount = $product->getData(self::AMOUNT_ATTRIBUTE);
        if ($amount === null || $amount === '') {
            return null;
        }

        $unitText = $product->getAttributeText(self::UNIT_ATTRIBUTE);
        if (!$unitText || $unitText === false) {
            return null;
        }

        // The raw amount is always decimal (e.g. 20.000000), so trim trailing zeros and dot
        $formattedAmount = rtrim(rtrim((string)$amount, '0'), '.');

        return $formattedAmount . ' ' . (string)$unitText;
    }

    /**
     * @return string[]
     */
    public function getDependsOnAttributeCodes(): array
    {
        return [self::AMOUNT_ATTRIBUTE, self::UNIT_ATTRIBUTE];
    }

    /**
     * Resolve the admin-scope (store_id=0) option label for a given option ID.
     * getAllOptions(withEmpty: false, defaultValues: true) forces load from store_id=0,
     * bypassing any frontend store-scope translation.
     */
    private function resolveAdminUnitLabel(string $rawOptionId): string
    {
        try {
            $attribute = $this->eavConfig->getAttribute(Product::ENTITY, self::UNIT_ATTRIBUTE);
            foreach ($attribute->getSource()->getAllOptions(false, true) as $option) {
                if ((string)$option['value'] === $rawOptionId) {
                    return (string)$option['label'];
                }
            }
        } catch (\Exception) {
            // fall through to empty string → resolver will use fallback label
        }

        return '';
    }
}
