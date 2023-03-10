<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQLPrinter\Settings;

use Closure;
use LastDragon_ru\LaraASP\GraphQLPrinter\Contracts\DirectiveFilter;
use LastDragon_ru\LaraASP\GraphQLPrinter\Contracts\Settings;
use LastDragon_ru\LaraASP\GraphQLPrinter\Contracts\TypeFilter;

abstract class ImmutableSettings implements Settings {
    protected string           $space;
    protected string           $indent;
    protected string           $fileEnd;
    protected string           $lineEnd;
    protected int              $lineLength;
    protected bool             $printDirectives;
    protected bool             $printDirectiveDefinitions;
    protected bool             $printUnusedDefinitions;
    protected bool             $normalizeSchema;
    protected bool             $normalizeUnions;
    protected bool             $normalizeEnums;
    protected bool             $normalizeInterfaces;
    protected bool             $normalizeFields;
    protected bool             $normalizeArguments;
    protected bool             $normalizeDescription;
    protected bool             $normalizeDirectiveLocations;
    protected bool             $alwaysMultilineUnions;
    protected bool             $alwaysMultilineArguments;
    protected bool             $alwaysMultilineInterfaces;
    protected bool             $alwaysMultilineDirectiveLocations;
    protected ?TypeFilter      $typeDefinitionFilter;
    protected ?DirectiveFilter $directiveFilter;
    protected ?DirectiveFilter $directiveDefinitionFilter;

    public function __construct() {
        // empty
    }

    public function getSpace(): string {
        return $this->space;
    }

    public function setSpace(string $value): static {
        return $this->set(static function (self $settings) use ($value): void {
            $settings->space = $value;
        });
    }

    public function getIndent(): string {
        return $this->indent;
    }

    public function setIndent(string $value): static {
        return $this->set(static function (self $settings) use ($value): void {
            $settings->indent = $value;
        });
    }

    public function getFileEnd(): string {
        return $this->fileEnd;
    }

    public function setFileEnd(string $value): static {
        return $this->set(static function (self $settings) use ($value): void {
            $settings->fileEnd = $value;
        });
    }

    public function getLineEnd(): string {
        return $this->lineEnd;
    }

    public function setLineEnd(string $value): static {
        return $this->set(static function (self $settings) use ($value): void {
            $settings->lineEnd = $value;
        });
    }

    public function getLineLength(): int {
        return $this->lineLength;
    }

    public function setLineLength(int $value): static {
        return $this->set(static function (self $settings) use ($value): void {
            $settings->lineLength = $value;
        });
    }

    public function isPrintUnusedDefinitions(): bool {
        return $this->printUnusedDefinitions;
    }

    public function setPrintUnusedDefinitions(bool $value): static {
        return $this->set(static function (self $settings) use ($value): void {
            $settings->printUnusedDefinitions = $value;
        });
    }

    public function isPrintDirectives(): bool {
        return $this->printDirectives;
    }

    public function setPrintDirectives(bool $value): static {
        return $this->set(static function (self $settings) use ($value): void {
            $settings->printDirectives = $value;
        });
    }

    public function isPrintDirectiveDefinitions(): bool {
        return $this->printDirectiveDefinitions;
    }

    public function setPrintDirectiveDefinitions(bool $value): static {
        return $this->set(static function (self $settings) use ($value): void {
            $settings->printDirectiveDefinitions = $value;
        });
    }

    public function isNormalizeSchema(): bool {
        return $this->normalizeSchema;
    }

    public function setNormalizeSchema(bool $value): static {
        return $this->set(static function (self $settings) use ($value): void {
            $settings->normalizeSchema = $value;
        });
    }

    public function isNormalizeUnions(): bool {
        return $this->normalizeUnions;
    }

    public function setNormalizeUnions(bool $value): static {
        return $this->set(static function (self $settings) use ($value): void {
            $settings->normalizeUnions = $value;
        });
    }

    public function isNormalizeEnums(): bool {
        return $this->normalizeEnums;
    }

    public function setNormalizeEnums(bool $value): static {
        return $this->set(static function (self $settings) use ($value): void {
            $settings->normalizeEnums = $value;
        });
    }

    public function isNormalizeInterfaces(): bool {
        return $this->normalizeInterfaces;
    }

    public function setNormalizeInterfaces(bool $value): static {
        return $this->set(static function (self $settings) use ($value): void {
            $settings->normalizeInterfaces = $value;
        });
    }

    public function isNormalizeFields(): bool {
        return $this->normalizeFields;
    }

    public function setNormalizeFields(bool $value): static {
        return $this->set(static function (self $settings) use ($value): void {
            $settings->normalizeFields = $value;
        });
    }

    public function isNormalizeArguments(): bool {
        return $this->normalizeArguments;
    }

    public function setNormalizeArguments(bool $value): static {
        return $this->set(static function (self $settings) use ($value): void {
            $settings->normalizeArguments = $value;
        });
    }

    public function isNormalizeDescription(): bool {
        return $this->normalizeDescription;
    }

    public function setNormalizeDescription(bool $value): static {
        return $this->set(static function (self $settings) use ($value): void {
            $settings->normalizeDescription = $value;
        });
    }

    public function isNormalizeDirectiveLocations(): bool {
        return $this->normalizeDirectiveLocations;
    }

    public function setNormalizeDirectiveLocations(bool $value): static {
        return $this->set(static function (self $settings) use ($value): void {
            $settings->normalizeDirectiveLocations = $value;
        });
    }

    public function isAlwaysMultilineUnions(): bool {
        return $this->alwaysMultilineUnions;
    }

    public function setAlwaysMultilineUnions(bool $value): static {
        return $this->set(static function (self $settings) use ($value): void {
            $settings->alwaysMultilineUnions = $value;
        });
    }

    public function isAlwaysMultilineArguments(): bool {
        return $this->alwaysMultilineArguments;
    }

    public function setAlwaysMultilineArguments(bool $value): static {
        return $this->set(static function (self $settings) use ($value): void {
            $settings->alwaysMultilineArguments = $value;
        });
    }

    public function isAlwaysMultilineInterfaces(): bool {
        return $this->alwaysMultilineInterfaces;
    }

    public function setAlwaysMultilineInterfaces(bool $value): static {
        return $this->set(static function (self $settings) use ($value): void {
            $settings->alwaysMultilineInterfaces = $value;
        });
    }

    public function isAlwaysMultilineDirectiveLocations(): bool {
        return $this->alwaysMultilineDirectiveLocations;
    }

    public function setAlwaysMultilineDirectiveLocations(bool $value): static {
        return $this->set(static function (self $settings) use ($value): void {
            $settings->alwaysMultilineDirectiveLocations = $value;
        });
    }

    public function getDirectiveFilter(): ?DirectiveFilter {
        return $this->directiveFilter;
    }

    public function setDirectiveFilter(?DirectiveFilter $value): static {
        return $this->set(static function (self $settings) use ($value): void {
            $settings->directiveFilter = $value;
        });
    }

    public function getTypeDefinitionFilter(): ?TypeFilter {
        return $this->typeDefinitionFilter;
    }

    public function setTypeDefinitionFilter(?TypeFilter $value): static {
        return $this->set(static function (self $settings) use ($value): void {
            $settings->typeDefinitionFilter = $value;
        });
    }

    public function getDirectiveDefinitionFilter(): ?DirectiveFilter {
        return $this->directiveDefinitionFilter;
    }

    public function setDirectiveDefinitionFilter(?DirectiveFilter $value): static {
        return $this->set(static function (self $settings) use ($value): void {
            $settings->directiveDefinitionFilter = $value;
        });
    }

    /**
     * @param Closure(static): void $callback
     */
    protected function set(Closure $callback): static {
        $settings = clone $this;

        $callback($settings);

        return $settings;
    }

    public static function createFrom(Settings $settings): self {
        return (new class() extends ImmutableSettings {
            // empty
        })
            ->setSpace($settings->getSpace())
            ->setIndent($settings->getIndent())
            ->setFileEnd($settings->getFileEnd())
            ->setLineEnd($settings->getLineEnd())
            ->setLineLength($settings->getLineLength())
            ->setPrintDirectives($settings->isPrintDirectives())
            ->setPrintDirectiveDefinitions($settings->isPrintDirectiveDefinitions())
            ->setPrintUnusedDefinitions($settings->isPrintUnusedDefinitions())
            ->setNormalizeSchema($settings->isNormalizeSchema())
            ->setNormalizeUnions($settings->isNormalizeUnions())
            ->setNormalizeEnums($settings->isNormalizeEnums())
            ->setNormalizeInterfaces($settings->isNormalizeInterfaces())
            ->setNormalizeFields($settings->isNormalizeFields())
            ->setNormalizeArguments($settings->isNormalizeArguments())
            ->setNormalizeDescription($settings->isNormalizeDescription())
            ->setNormalizeDirectiveLocations($settings->isNormalizeDirectiveLocations())
            ->setAlwaysMultilineUnions($settings->isAlwaysMultilineUnions())
            ->setAlwaysMultilineArguments($settings->isAlwaysMultilineArguments())
            ->setAlwaysMultilineInterfaces($settings->isAlwaysMultilineInterfaces())
            ->setAlwaysMultilineDirectiveLocations($settings->isAlwaysMultilineDirectiveLocations())
            ->setTypeDefinitionFilter($settings->getTypeDefinitionFilter())
            ->setDirectiveDefinitionFilter($settings->getDirectiveDefinitionFilter())
            ->setDirectiveFilter($settings->getDirectiveFilter());
    }
}
