{{--
    Edit Menu page — replaces the default EditRecord form with a full-page
    drag-and-drop tree editor for menu items.

    The tree component (from solution-forest/filament-tree) renders the
    hierarchical list of MenuItem records belonging to this Menu.
    - Drag and drop to reorder / re-parent nodes
    - Inline Edit and Delete actions on each node
    - Save button inside the tree component persists the new order
--}}
<x-filament-panels::page>
    <x-filament::section>
        <x-slot name="heading">
            {{ __('Menu Settings') }}
        </x-slot>

        <x-filament-panels::form
            id="form"
            :wire:key="$this->getId() . '.forms.' . $this->getFormStatePath()"
            wire:submit="save"
        >
            {{ $this->form }}

            <x-filament-panels::form.actions
                :actions="$this->getCachedFormActions()"
                :full-width="$this->hasFullWidthFormActions()"
            />
        </x-filament-panels::form>
    </x-filament::section>

    <x-filament::section>
        <x-slot name="heading">
            {{ __('Menu Items') }}
        </x-slot>

        {{ $this->tree }}
    </x-filament::section>
</x-filament-panels::page>
