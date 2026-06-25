<?php

declare(strict_types=1);

namespace SR\BasePriceLinkedProduct\Test\Unit\Model;

use PHPUnit\Framework\TestCase;
use SR\BasePriceLinkedProduct\Model\UnitCategoryResolver;

class UnitCategoryResolverTest extends TestCase
{
    private function buildResolver(): UnitCategoryResolver
    {
        return new UnitCategoryResolver(
            unitCategoryMap: [
                'kg' => 'Weight',
                'g'  => 'Weight',
                'mg' => 'Weight',
                'l'  => 'Volume',
                'ml' => 'Volume',
                'm'  => 'Size',
                'cm' => 'Size',
                'mm' => 'Size',
            ],
            fallbackLabel: 'Units',
        );
    }

    /**
     * @dataProvider unitToExpectedCategoryProvider
     */
    public function testResolvesMappedUnits(string $unit, string $expectedCategory): void
    {
        $resolver = $this->buildResolver();
        // __() is not available in unit tests; rely on the raw string returned
        // (translation layer is Magento infrastructure, not testable here)
        $this->assertSame($expectedCategory, $resolver->resolve($unit));
    }

    public static function unitToExpectedCategoryProvider(): array
    {
        return [
            ['kg',  'Weight'],
            ['g',   'Weight'],
            ['mg',  'Weight'],
            ['l',   'Volume'],
            ['ml',  'Volume'],
            ['m',   'Size'],
            ['cm',  'Size'],
            ['mm',  'Size'],
        ];
    }

    public function testUnknownUnitReturnsFallback(): void
    {
        $resolver = $this->buildResolver();
        $this->assertSame('Units', $resolver->resolve('oz'));
        $this->assertSame('Units', $resolver->resolve(''));
    }

    public function testInputIsCaseInsensitive(): void
    {
        $resolver = $this->buildResolver();
        $this->assertSame('Weight', $resolver->resolve('KG'));
        $this->assertSame('Weight', $resolver->resolve('Kg'));
        $this->assertSame('Volume', $resolver->resolve('ML'));
    }

    public function testCustomFallbackLabelIsUsed(): void
    {
        $resolver = new UnitCategoryResolver([], 'Measurement');
        $this->assertSame('Measurement', $resolver->resolve('unknown'));
    }
}
