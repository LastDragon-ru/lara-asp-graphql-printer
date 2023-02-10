<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\Definitions;

use GraphQL\Type\Definition\InterfaceType;
use LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\Block;
use LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\Types\TypeBlock;
use LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\Types\UsageList;

/**
 * @internal
 * @extends UsageList<TypeBlock, InterfaceType>
 */
class ImplementsInterfaces extends UsageList {
    protected function block(mixed $item): Block {
        return new TypeBlock(
            $this->getSettings(),
            $this->getLevel() + 1,
            $this->getUsed(),
            $item,
        );
    }

    protected function separator(): string {
        return '&';
    }

    protected function prefix(): string {
        return 'implements';
    }

    protected function isNormalized(): bool {
        return $this->getSettings()->isNormalizeInterfaces();
    }

    protected function isAlwaysMultiline(): bool {
        return $this->getSettings()->isAlwaysMultilineInterfaces();
    }
}