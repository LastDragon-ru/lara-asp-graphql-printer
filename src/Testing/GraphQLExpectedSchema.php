<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQLPrinter\Testing;

use GraphQL\Language\AST\DocumentNode;
use GraphQL\Type\Schema;
use LastDragon_ru\LaraASP\GraphQLPrinter\Contracts\Settings;
use SplFileInfo;

class GraphQLExpectedSchema extends GraphQLExpected {
    /**
     * @inheritDoc
     */
    public function __construct(
        protected Schema|DocumentNode|SplFileInfo|string $schema,
        ?array $usedTypes = null,
        ?array $usedDirectives = null,
        ?Settings $settings = null,
    ) {
        parent::__construct($usedTypes, $usedDirectives, $settings);
    }

    public function getSchema(): Schema|DocumentNode|SplFileInfo|string {
        return $this->schema;
    }
}
