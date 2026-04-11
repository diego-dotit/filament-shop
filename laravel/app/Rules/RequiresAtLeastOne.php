<?php

namespace App\Rules;

use Filament\Forms\Get;
use Illuminate\Contracts\Validation\ImplicitRule;

class RequiresAtLeastOne implements ImplicitRule
{
    public function __construct(
        private readonly string $sibling,
        private readonly Get $get,
    ) {}

    public function passes($attribute, $value): bool
    {
        return ! blank($value) || ! blank(($this->get)($this->sibling));
    }

    public function message(): string
    {
        return 'At least one of Customer or Author must be specified.';
    }
}
