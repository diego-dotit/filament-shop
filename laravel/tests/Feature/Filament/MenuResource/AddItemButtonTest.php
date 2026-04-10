<?php

namespace Tests\Feature\Filament\MenuResource;

use App\Domains\Menu\Models\MenuItem;
use App\Filament\Resources\MenuResource\Pages\EditMenu;
use Filament\Actions\Action;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddItemButtonTest extends TestCase
{
    use RefreshDatabase;
    // -----------------------------------------------------------------------
    // EditMenu — "Add item" header action
    // -----------------------------------------------------------------------

    public function test_edit_menu_has_add_item_header_action(): void
    {
        $reflection = new \ReflectionClass(EditMenu::class);
        $this->assertTrue($reflection->hasMethod('getHeaderActions'));

        $instance = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('getHeaderActions');
        $method->setAccessible(true);
        $actions = $method->invoke($instance);

        $actionNames = array_map(
            fn ($a) => method_exists($a, 'getName') ? $a->getName() : null,
            $actions
        );

        $this->assertContains('add_menu_item', $actionNames, 'Expected "add_menu_item" action in header actions');
    }

    public function test_edit_menu_add_item_action_is_filament_action(): void
    {
        $reflection = new \ReflectionClass(EditMenu::class);
        $instance = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('getHeaderActions');
        $method->setAccessible(true);
        $actions = $method->invoke($instance);

        $addItemAction = null;

        foreach ($actions as $action) {
            if (method_exists($action, 'getName') && $action->getName() === 'add_menu_item') {
                $addItemAction = $action;
                break;
            }
        }

        $this->assertNotNull($addItemAction, 'add_menu_item action not found');
        $this->assertInstanceOf(Action::class, $addItemAction);
    }

    public function test_edit_menu_add_item_action_has_label(): void
    {
        $reflection = new \ReflectionClass(EditMenu::class);
        $instance = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('getHeaderActions');
        $method->setAccessible(true);
        $actions = $method->invoke($instance);

        $addItemAction = null;

        foreach ($actions as $action) {
            if (method_exists($action, 'getName') && $action->getName() === 'add_menu_item') {
                $addItemAction = $action;
                break;
            }
        }

        $this->assertNotNull($addItemAction);
        $this->assertSame('Add item', $addItemAction->getLabel());
    }

    public function test_edit_menu_get_add_item_form_schema_returns_array(): void
    {
        $reflection = new \ReflectionClass(EditMenu::class);
        $this->assertTrue($reflection->hasMethod('getEditFormSchema'));

        $instance = $reflection->newInstanceWithoutConstructor();
        $method = $reflection->getMethod('getEditFormSchema');
        $method->setAccessible(true);
        $schema = $method->invoke($instance);

        $this->assertIsArray($schema);
        $this->assertNotEmpty($schema);
    }
}
