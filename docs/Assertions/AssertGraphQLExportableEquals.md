# `assertGraphQLExportableEquals`

Exports and compares two GraphQL schemas/types/nodes/etc.

[include:example]: ./AssertGraphQLExportableEqualsTest.php
[//]: # (start: 2852b7456553efa57ca27c58f94107dd1a13536fc09ad9878c4f8ff405c53045)
[//]: # (warning: Generated automatically. Do not edit.)

```php
<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQLPrinter\Docs\Assertions;

use GraphQL\Utils\BuildSchema;
use LastDragon_ru\LaraASP\GraphQLPrinter\Testing\GraphQLAssertions;
use LastDragon_ru\LaraASP\GraphQLPrinter\Testing\GraphQLExpected;
use PHPUnit\Framework\Attributes\CoversNothing;
use PHPUnit\Framework\TestCase;

/**
 * @internal
 */
#[CoversNothing]
final class AssertGraphQLExportableEqualsTest extends TestCase {
    /**
     * Trait where assertion defined.
     */
    use GraphQLAssertions;

    /**
     * Assertion test.
     */
    public function testAssertion(): void {
        // Prepare
        $schema   = BuildSchema::build(
            <<<'GRAPHQL'
            directive @a(b: B) on OBJECT

            type Query {
                a: A
            }

            type A @a {
                id: ID!
            }

            input B {
                b: String!
            }
            GRAPHQL,
        );
        $type     = $schema->getType('A');
        $expected = <<<'GRAPHQL'
            type A
            @a
            {
                id: ID!
            }

            directive @a(
                b: B
            )
            on
                | OBJECT

            input B {
                b: String!
            }

            GRAPHQL;

        self::assertNotNull($type);

        // Test
        // (schema required to find types/directives definition)
        $this->assertGraphQLExportableEquals(
            (new GraphQLExpected($expected))->setSchema($schema),
            $type,
        );
    }
}
```

[//]: # (end: 2852b7456553efa57ca27c58f94107dd1a13536fc09ad9878c4f8ff405c53045)
