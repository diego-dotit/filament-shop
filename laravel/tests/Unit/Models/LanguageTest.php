<?php

namespace Tests\Unit\Models;

use App\Domains\Language\Models\Language;
use Illuminate\Database\Eloquent\Model;
use PHPUnit\Framework\TestCase;

class LanguageTest extends TestCase
{
    public function test_language_extends_eloquent_model(): void
    {
        $language = new Language();

        $this->assertInstanceOf(Model::class, $language);
    }

    public function test_language_has_correct_fillable_fields(): void
    {
        $language = new Language();

        $this->assertSame(['code', 'name', 'is_default'], $language->getFillable());
    }

    public function test_language_casts_is_default_as_boolean(): void
    {
        $language = new Language();
        $casts = $language->getCasts();

        $this->assertArrayHasKey('is_default', $casts);
        $this->assertSame('boolean', $casts['is_default']);
    }

    public function test_is_default_is_true_when_set_to_one(): void
    {
        $language = new Language(['is_default' => 1]);

        $this->assertTrue($language->is_default);
    }

    public function test_is_default_is_false_when_set_to_zero(): void
    {
        $language = new Language(['is_default' => 0]);

        $this->assertFalse($language->is_default);
    }

    public function test_language_has_no_relationships(): void
    {
        $reflection = new \ReflectionClass(Language::class);

        // Collect public instance (non-static) methods declared on Language itself
        $instanceMethods = array_map(
            fn(\ReflectionMethod $m) => $m->getName(),
            array_filter(
                $reflection->getMethods(\ReflectionMethod::IS_PUBLIC),
                fn(\ReflectionMethod $m) => $m->getDeclaringClass()->getName() === Language::class
                    && ! $m->isStatic()
            )
        );

        $this->assertEmpty(
            $instanceMethods,
            'Language model should define no custom public instance methods (no relationships)'
        );
    }

    public function test_language_namespace_is_correct(): void
    {
        $this->assertSame(
            'App\Domains\Language\Models\Language',
            Language::class
        );
    }
}
