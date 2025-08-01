<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQLPrinter;

use GraphQL\Type\Schema;
use LastDragon_ru\LaraASP\GraphQLPrinter\Contracts\Settings;
use LastDragon_ru\LaraASP\GraphQLPrinter\Settings\GraphQLSettings;
use LastDragon_ru\LaraASP\GraphQLPrinter\Testing\Package\TestCase;
use LastDragon_ru\LaraASP\GraphQLPrinter\Testing\TestSettings;
use LastDragon_ru\LaraASP\Testing\Requirements\Requirements\RequiresComposerPackage;
use PHPUnit\Framework\Attributes\CoversClass;
use PHPUnit\Framework\Attributes\DataProvider;

/**
 * @internal
 */
#[CoversClass(IntrospectionPrinter::class)]
final class IntrospectionPrinterTest extends TestCase {
    // <editor-fold desc="Tests">
    // =========================================================================
    #[DataProvider('dataProviderPrint')]
    #[RequiresComposerPackage('webonyx/graphql-php', '>=15.22.0')]
    public function testPrint(string $expected, Settings $settings, int $level): void {
        $expected = self::getTestData()->content($expected);
        $printer  = (new IntrospectionPrinter())->setSettings($settings);
        $schema   = new Schema([]);
        $actual   = $printer->print($schema, $level);

        self::assertSame($expected, (string) $actual);
    }
    // </editor-fold>

    // <editor-fold desc="DataProviders">
    // =========================================================================
    /**
     * @return array<string, array{string, Settings, int}>
     */
    public static function dataProviderPrint(): array {
        return [
            GraphQLSettings::class         => [
                '~GraphQLSettings.graphql',
                new GraphQLSettings(),
                0,
            ],
            TestSettings::class            => [
                '~TestSettings.graphql',
                new TestSettings(),
                0,
            ],
            TestSettings::class.' (level)' => [
                '~TestSettings-level.graphql',
                new TestSettings(),
                1,
            ],
        ];
    }
    // </editor-fold>
}
