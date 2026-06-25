<?php

declare(strict_types=1);

namespace SR\BasePriceLinkedProduct\Test\Unit\Model;

use Magento\Catalog\Model\Product;
use Magento\Eav\Model\Config as EavConfig;
use Magento\Eav\Model\Entity\Attribute\AbstractAttribute;
use Magento\Eav\Model\Entity\Attribute\Source\AbstractSource;
use PHPUnit\Framework\TestCase;
use SR\BasePriceLinkedProduct\Model\BasePriceVirtualAttribute;
use SR\BasePriceLinkedProduct\Model\UnitCategoryResolver;

class BasePriceVirtualAttributeTest extends TestCase
{
    private UnitCategoryResolver $resolver;
    private EavConfig $eavConfig;

    protected function setUp(): void
    {
        $this->resolver = new UnitCategoryResolver(
            unitCategoryMap: [
                'kg' => 'Weight',
                'g'  => 'Weight',
                'l'  => 'Volume',
                'ml' => 'Volume',
                'm'  => 'Size',
                'cm' => 'Size',
            ],
            fallbackLabel: 'Units',
        );
        $this->eavConfig = $this->createMock(EavConfig::class);
    }

    private function buildAttribute(): BasePriceVirtualAttribute
    {
        return new BasePriceVirtualAttribute($this->eavConfig, $this->resolver);
    }

    private function buildProduct(
        ?float $amount,
        string|false|null $unitText,
        ?string $unitOptionId = '42'
    ): Product {
        $product = $this->createMock(Product::class);
        $product->method('getData')->willReturnCallback(
            fn(string $key) => match ($key) {
                'baseprice_product_amount' => $amount,
                'baseprice_product_unit'   => $unitOptionId,
                default                   => null,
            }
        );
        $product->method('getAttributeText')->willReturnCallback(
            fn(string $key) => $key === 'baseprice_product_unit' ? $unitText : false
        );
        return $product;
    }

    private function mockAdminOptions(string $optionId, string $adminLabel): void
    {
        $source = $this->createMock(AbstractSource::class);
        $source->method('getAllOptions')
            ->with(false, true)
            ->willReturn([['value' => $optionId, 'label' => $adminLabel]]);

        $attribute = $this->createMock(AbstractAttribute::class);
        $attribute->method('getSource')->willReturn($source);

        $this->eavConfig->method('getAttribute')
            ->with(Product::ENTITY, 'baseprice_product_unit')
            ->willReturn($attribute);
    }

    public function testAttributeCode(): void
    {
        $this->assertSame('sr_baseprice_amount_unit', $this->buildAttribute()->getAttributeCode());
    }

    public function testAdminLabel(): void
    {
        $this->assertSame('Product Amount and Unit', $this->buildAttribute()->getAdminLabel());
    }

    public function testGetValueForProductReturnsCombinedString(): void
    {
        $product = $this->buildProduct(3.0, 'kg');
        $this->assertSame('3 kg', $this->buildAttribute()->getValueForProduct($product));
    }

    public function testGetValueForProductStripsTrailingZeros(): void
    {
        $product = $this->buildProduct(3.00, 'kg');
        $this->assertSame('3 kg', $this->buildAttribute()->getValueForProduct($product));

        $product = $this->buildProduct(3.50, 'kg');
        $this->assertSame('3.5 kg', $this->buildAttribute()->getValueForProduct($product));
    }

    public function testGetValueForProductWithHebrewFrontendText(): void
    {
        // Frontend text is Hebrew — value should use that, not admin label
        $product = $this->buildProduct(3.0, 'ק״ג');
        $this->assertSame('3 ק״ג', $this->buildAttribute()->getValueForProduct($product));
    }

    public function testGetValueForProductNullAmountReturnsNull(): void
    {
        $product = $this->buildProduct(null, 'kg');
        $this->assertNull($this->buildAttribute()->getValueForProduct($product));
    }

    public function testGetValueForProductEmptyUnitTextReturnsNull(): void
    {
        $product = $this->buildProduct(3.0, '');
        $this->assertNull($this->buildAttribute()->getValueForProduct($product));
    }

    public function testGetValueForProductFalseUnitTextReturnsNull(): void
    {
        $product = $this->buildProduct(3.0, false);
        $this->assertNull($this->buildAttribute()->getValueForProduct($product));
    }

    public function testGetFrontendLabelResolvesWeightForKg(): void
    {
        $this->mockAdminOptions('42', 'kg');
        $product = $this->buildProduct(3.0, 'kg', '42');
        $this->assertSame('Weight', $this->buildAttribute()->getFrontendLabel($product));
    }

    public function testGetFrontendLabelResolvesVolumeForL(): void
    {
        $this->mockAdminOptions('10', 'l');
        $product = $this->buildProduct(1.0, 'l', '10');
        $this->assertSame('Volume', $this->buildAttribute()->getFrontendLabel($product));
    }

    public function testGetFrontendLabelUsesFallbackForUnknownUnit(): void
    {
        $this->mockAdminOptions('99', 'oz');
        $product = $this->buildProduct(1.0, 'oz', '99');
        $this->assertSame('Units', $this->buildAttribute()->getFrontendLabel($product));
    }

    public function testGetFrontendLabelUsesAdminLabelNotFrontendText(): void
    {
        // Hebrew frontend text ('ק״ג'), but admin label is 'kg' → should still resolve to 'Weight'
        $this->mockAdminOptions('42', 'kg');
        $product = $this->buildProduct(3.0, 'ק״ג', '42'); // Hebrew frontend text
        $this->assertSame('Weight', $this->buildAttribute()->getFrontendLabel($product));
    }

    public function testDependsOnAttributeCodes(): void
    {
        $codes = $this->buildAttribute()->getDependsOnAttributeCodes();
        $this->assertContains('baseprice_product_amount', $codes);
        $this->assertContains('baseprice_product_unit', $codes);
    }
}
