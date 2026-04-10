<?php

namespace Tests\Feature\Filament\MenuResource;

use App\Filament\Resources\MenuResource;
use App\Filament\Resources\MenuResource\Pages\EditMenu;
use App\Filament\Resources\MenuResource\Pages\ListMenus;
use Filament\Actions\CreateAction;
use Filament\Resources\Pages\EditRecord;
use Filament\Resources\Pages\ListRecords;
use SolutionForest\FilamentTree\Concern\InteractWithTree;
use SolutionForest\FilamentTree\Contract\HasTree;
use Tests\TestCase;

class PagesTest extends TestCase
{
    // -----------------------------------------------------------------------
    // ListMenus
    // -----------------------------------------------------------------------

    public function test_list_menus_class_exists(): void
    {
        $this->assertTrue(class_exists(ListMenus::class));
    }

    public function test_list_menus_extends_list_records(): void
    {
        $this->assertTrue(is_subclass_of(ListMenus::class, ListRecords::class));
    }

    public function test_list_menus_has_correct_resource(): void
    {
        $reflection = new \ReflectionClass(ListMenus::class);
        $property = $reflection->getProperty('resource');
        $property->setAccessible(true);

        $instance = $reflection->newInstanceWithoutConstructor();

        $this->assertSame(MenuResource::class, $property->getValue($instance));
    }

    public function test_list_menus_get_header_actions_returns_create_action(): void
    {
        $reflection = new \ReflectionClass(ListMenus::class);
        $method = $reflection->getMethod('getHeaderActions');
        $method->setAccessible(true);

        $instance = $reflection->newInstanceWithoutConstructor();
        $actions = $method->invoke($instance);

        $this->assertCount(1, $actions);
        $this->assertInstanceOf(CreateAction::class, $actions[0]);
    }

    public function test_list_menus_create_action_has_label(): void
    {
        $reflection = new \ReflectionClass(ListMenus::class);
        $method = $reflection->getMethod('getHeaderActions');
        $method->setAccessible(true);

        $instance = $reflection->newInstanceWithoutConstructor();
        $actions = $method->invoke($instance);

        $createAction = $actions[0];
        $this->assertSame('Create Menu', $createAction->getLabel());
    }

    // -----------------------------------------------------------------------
    // EditMenu
    // -----------------------------------------------------------------------

    public function test_edit_menu_class_exists(): void
    {
        $this->assertTrue(class_exists(EditMenu::class));
    }

    public function test_edit_menu_extends_edit_record(): void
    {
        $this->assertTrue(is_subclass_of(EditMenu::class, EditRecord::class));
    }

    public function test_edit_menu_has_correct_resource(): void
    {
        $reflection = new \ReflectionClass(EditMenu::class);
        $property = $reflection->getProperty('resource');
        $property->setAccessible(true);

        $instance = $reflection->newInstanceWithoutConstructor();

        $this->assertSame(MenuResource::class, $property->getValue($instance));
    }

    public function test_edit_menu_uses_interact_with_tree_trait(): void
    {
        $traits = [];
        $class = EditMenu::class;

        while ($class) {
            $traits = array_merge($traits, class_uses($class));
            $class = get_parent_class($class);
        }

        $this->assertArrayHasKey(InteractWithTree::class, $traits);
    }

    public function test_edit_menu_implements_has_tree_interface(): void
    {
        $this->assertTrue(
            in_array(HasTree::class, class_implements(EditMenu::class))
        );
    }

    public function test_edit_menu_has_get_title_method(): void
    {
        $this->assertTrue(method_exists(EditMenu::class, 'getTitle'));
    }

    public function test_edit_menu_has_get_redirect_url_method(): void
    {
        $reflection = new \ReflectionClass(EditMenu::class);
        $this->assertTrue($reflection->hasMethod('getRedirectUrl'));
    }

    public function test_edit_menu_has_static_tree_method(): void
    {
        $reflection = new \ReflectionClass(EditMenu::class);
        $this->assertTrue($reflection->hasMethod('tree'));
        $this->assertTrue($reflection->getMethod('tree')->isStatic());
    }

    public function test_edit_menu_has_get_max_depth_method(): void
    {
        $this->assertTrue(method_exists(EditMenu::class, 'getMaxDepth'));
    }

    public function test_edit_menu_overrides_view(): void
    {
        $reflection = new \ReflectionClass(EditMenu::class);
        $this->assertTrue($reflection->hasMethod('getView'));
    }

    public function test_edit_menu_get_redirect_url_declared_in_edit_menu(): void
    {
        $reflection = new \ReflectionClass(EditMenu::class);
        $method = $reflection->getMethod('getRedirectUrl');
        $this->assertSame(EditMenu::class, $method->getDeclaringClass()->getName());
    }
}
