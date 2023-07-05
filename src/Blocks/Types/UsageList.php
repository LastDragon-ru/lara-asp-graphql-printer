<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\Types;

use LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\Block;
use LastDragon_ru\LaraASP\GraphQLPrinter\Blocks\ListBlock;
use LastDragon_ru\LaraASP\GraphQLPrinter\Misc\Context;

use function mb_strlen;

/**
 * @internal
 * @template TBlock of Block
 * @template TType
 * @extends ListBlock<TBlock>
 */
abstract class UsageList extends ListBlock {
    /**
     * @param iterable<TType> $items
     */
    public function __construct(
        Context $context,
        int $level,
        int $used,
        iterable $items,
        protected bool $isAlwaysMultiline = false,
    ) {
        parent::__construct($context, $level, $used);

        foreach ($items as $item) {
            $this[] = $this->block($level, $used, $item);
        }
    }

    public function isMultiline(int $level, int $used): bool {
        return parent::isMultiline($level, $used) || $this->isAlwaysMultiline();
    }

    protected function isAlwaysMultiline(): bool {
        return $this->isAlwaysMultiline;
    }

    /**
     * @param TType $item
     *
     * @return TBlock
     */
    abstract protected function block(int $level, int $used, mixed $item): Block;

    abstract protected function separator(): string;

    abstract protected function prefix(): string;

    protected function getSeparator(): string {
        return "{$this->space()}{$this->separator()}{$this->space()}";
    }

    protected function getMultilineItemPrefix(): string {
        return "{$this->separator()}{$this->space()}";
    }

    protected function content(int $level, int $used): string {
        $level   = $level + 1;
        $prefix  = $this->prefix();
        $content = parent::content($level, $used);

        if ($content) {
            if ($this->isAlwaysMultiline() || $this->isStringMultiline($content)) {
                $eol    = $this->eol();
                $indent = $this->indent($level);

                if ($prefix) {
                    $content = "{$prefix}{$eol}{$indent}{$content}";
                }
            } else {
                $space = $this->space();

                if ($prefix) {
                    $content = "{$prefix}{$space}{$content}";
                }
            }
        }

        return $content;
    }

    protected function getUsed(): int {
        return parent::getUsed() + mb_strlen("{$this->prefix()}{$this->space()}");
    }
}
