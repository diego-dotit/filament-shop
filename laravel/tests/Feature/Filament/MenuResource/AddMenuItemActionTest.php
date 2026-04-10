<?php

namespace Tests\Feature\Filament\MenuResource;

use App\Filament\Resources\MenuResource\Pages\EditMenu;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AddMenuItemActionTest extends TestCase
{
    use RefreshDatabase;
    // -----------------------------------------------------------------------
    // Header action count
    // -----------------------------------------------------------------------

    public function test_get_header_actions_returns_two_actions(): void
    {
        $reflection = new \ReflectionClass(EditMenu::class);
        $method = $reflection->getMethod('getHeaderActions');
        $method->setAccessible(true);

        $instance = $reflection->newInstanceWithoutConstructor();
        $actions = $method->invoke($instance);

        $this->assertCount(2, $actions);
    }

    // -----------------------------------------------------------------------
    // First action is still the DeleteAction
    // -----------------------------------------------------------------------

    public function test_first_header_action_is_delete_action(): void
    {
        $reflection = new \ReflectionClass(EditMenu::class);
        $method = $reflection->getMethod('getHeaderActions');
        $method->setAccessible(true);

        $instance = $reflection->newInstanceWithoutConstructor();
        $actions = $method->invoke($instance);

        $this->assertInstanceOf(DeleteAction::class, $actions[0]);
    }

    // -----------------------------------------------------------------------
    // Second action is the "Add item" action
    // -----------------------------------------------------------------------

    public function test_second_header_action_is_add_item_action(): void
    {
        $reflection = new \ReflectionClass(EditMenu::class);
        $method = $reflection->getMethod('getHeaderActions');
        $method->setAccessible(true);

        $instance = $reflection->newInstanceWithoutConstructor();
        $actions = $method->invoke($instance);

        $this->assertInstanceOf(Action::class, $actions[1]);
    }

    public function test_add_item_action_has_correct_name(): void
    {
        $reflection = new \ReflectionClass(EditMenu::class);
        $method = $reflection->getMethod('getHeaderActions');
        $method->setAccessible(true);

        $instance = $reflection->newInstanceWithoutConstructor();
        $actions = $method->invoke($instance);

        $this->assertSame('add_menu_item', $actions[1]->getName());
    }

    public function test_add_item_action_has_correct_label(): void
    {
        $reflection = new \ReflectionClass(EditMenu::class);
        $method = $reflection->getMethod('getHeaderActions');
        $method->setAccessible(true);

        $instance = $reflection->newInstanceWithoutConstructor();
        $actions = $method->invoke($instance);

        $this->assertSame('Add item', $actions[1]->getLabel());
    }

    public function test_add_item_action_has_correct_modal_heading(): void
    {
        $reflection = new \ReflectionClass(EditMenu::class);
        $method = $reflection->getMethod('getHeaderActions');
        $method->setAccessible(true);

        $instance = $reflection->newInstanceWithoutConstructor();
        $actions = $method->invoke($instance);

        $addItemAction = $actions[1];
        $this->assertSame('Add Menu Item', $addItemAction->getModalHeading());
    }

    // -----------------------------------------------------------------------
    // Form schema includes required fields
    // -----------------------------------------------------------------------

    public function test_get_edit_form_schema_returns_array(): void
    {
        $reflection = new \ReflectionClass(EditMenu::class);
        $method = $reflection->getMethod('getEditFormSchema');
        $method->setAccessible(true);

        $instance = $reflection->newInstanceWithoutConstructor();
        $schema = $method->invoke($instance);

        $this->assertIsArray($schema);
        $this->assertNotEmpty($schema);
    }

    public function test_get_edit_form_schema_includes_type_select(): void
    {
        $reflection = new \ReflectionClass(EditMenu::class);
        $method = $reflection->getMethod('getEditFormSchema');
        $method->setAccessible(true);

        $instance = $reflection->newInstanceWithoutConstructor();
        $schema = $method->invoke($instance);

        $hasTypeSelect = false;
        foreach ($schema as $component) {
            if ($component instanceof \Filament\Forms\Components\Select
                && $component->getName() === 'type'
            ) {
                $hasTypeSelect = true;
                break;
            }
        }

        $this->assertTrue($hasTypeSelect, 'Form schema should include a "type" Select component');
    }

    public function test_get_edit_form_schema_includes_value_id_select(): void
    {
        $reflection = new \ReflectionClass(EditMenu::class);
        $method = $reflection->getMethod('getEditFormSchema');
        $method->setAccessible(true);

        $instance = $reflection->newInstanceWithoutConstructor();
        $schema = $method->invoke($instance);

        $hasValueId = false;
        foreach ($schema as $component) {
            if ($component instanceof \Filament\Forms\Components\Select
                && $component->getName() === 'value_id'
            ) {
                $hasValueId = true;
                break;
            }
        }

        $this->assertTrue($hasValueId, 'Form schema should include a "value_id" Select component');
    }
}
