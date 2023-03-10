<?php declare(strict_types = 1);

namespace LastDragon_ru\LaraASP\GraphQLPrinter\Contracts;

use GraphQL\Type\Definition\Type;
use GraphQL\Type\Schema;

interface Printer {
    public function printSchema(Schema $schema): Result;

    public function printSchemaType(Schema $schema, Type|string $type): Result;

    public function printType(Type $type): Result;

    public function getLevel(): int;

    public function setLevel(int $level): static;

    public function getSettings(): Settings;

    public function setSettings(?Settings $settings): static;

    public function getDirectiveResolver(): ?DirectiveResolver;

    public function setDirectiveResolver(?DirectiveResolver $directiveResolver): static;
}
