<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQLPrinter\Settings;

use LastDragon_ru\LaraASP\GraphQLPrinter\Contracts\Settings;
use LastDragon_ru\LaraASP\GraphQLPrinter\Testing\Package\TestCase;
use Mockery;
use ReflectionClass;
use ReflectionMethod;

/**
 * @internal
 * @covers \LastDragon_ru\LaraASP\GraphQLPrinter\Settings\ImmutableSettings
 */
class ImmutableSettingsTest extends TestCase {
    public function testCreateFrom(): void {
        $methods  = (new ReflectionClass(Settings::class))->getMethods(ReflectionMethod::IS_PUBLIC);
        $settings = Mockery::mock(Settings::class);

        foreach ($methods as $method) {
            $settings
                ->shouldReceive($method->getName())
                ->once();
        }

        ImmutableSettings::createFrom($settings);
    }
}
