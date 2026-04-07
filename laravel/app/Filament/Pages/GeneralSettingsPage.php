<?php

namespace App\Filament\Pages;

use App\Domains\Language\Models\Language;
use App\Settings\GeneralSettings;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;

class GeneralSettingsPage extends Page
{
    use InteractsWithFormActions;

    protected static ?string $navigationGroup = 'Configuration';

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'General Settings';

    protected static string $view = 'filament.pages.general-settings-page';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $settings = app(GeneralSettings::class);
        $languages = Language::orderByDesc('is_default')->orderBy('name')->get();

        $formData = [
            'is_open' => $settings->is_open,
            'logo' => $settings->logo,
            'favicon' => $settings->favicon,
        ];

        foreach ($languages as $language) {
            $code = $language->code;

            $formData["site_title_{$code}"] = $settings->site_title[$code] ?? '';
            $formData["meta_title_{$code}"] = $settings->meta_title[$code] ?? '';
            $formData["meta_description_{$code}"] = $settings->meta_description[$code] ?? '';
            $formData["meta_keywords_{$code}"] = $settings->meta_keywords[$code] ?? '';
        }

        $this->form->fill($formData);
    }

    public function form(Form $form): Form
    {
        $languages = Language::orderByDesc('is_default')->orderBy('name')->get();

        $tabs = $languages->map(function (Language $language): Tabs\Tab {
            $code = $language->code;

            return Tabs\Tab::make($language->name)
                ->schema([
                    TextInput::make("site_title_{$code}")
                        ->label('Site Title')
                        ->maxLength(255)
                        ->nullable(),

                    TextInput::make("meta_title_{$code}")
                        ->label('Meta Title')
                        ->maxLength(255)
                        ->nullable(),

                    TextInput::make("meta_description_{$code}")
                        ->label('Meta Description')
                        ->maxLength(255)
                        ->nullable(),

                    TextInput::make("meta_keywords_{$code}")
                        ->label('Meta Keywords')
                        ->maxLength(255)
                        ->nullable(),
                ]);
        })->toArray();

        return $form
            ->schema([
                Section::make('Translations')
                    ->schema([
                        Tabs::make('Translations')
                            ->tabs($tabs)
                            ->columnSpanFull(),
                    ]),

                Section::make('Media')
                    ->schema([
                        FileUpload::make('logo')
                            ->label('Logo')
                            ->disk('public')
                            ->directory('settings')
                            ->image()
                            ->imageEditor()
                            ->nullable(),

                        FileUpload::make('favicon')
                            ->label('Favicon')
                            ->disk('public')
                            ->directory('settings')
                            ->image()
                            ->nullable(),
                    ])
                    ->columns(2),

                Section::make('Configuration')
                    ->schema([
                        Toggle::make('is_open')
                            ->label('Website Open to Public')
                            ->default(true),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $settings = app(GeneralSettings::class);
        $languages = Language::orderByDesc('is_default')->orderBy('name')->get();

        foreach ($languages as $language) {
            $code = $language->code;

            $settings->site_title[$code] = $data["site_title_{$code}"] ?? '';
            $settings->meta_title[$code] = $data["meta_title_{$code}"] ?? '';
            $settings->meta_description[$code] = $data["meta_description_{$code}"] ?? '';
            $settings->meta_keywords[$code] = $data["meta_keywords_{$code}"] ?? '';
        }

        $settings->is_open = (bool) ($data['is_open'] ?? true);
        $settings->logo = $data['logo'] ?? null;
        $settings->favicon = $data['favicon'] ?? null;

        $settings->save();

        Notification::make()
            ->title('General settings saved successfully.')
            ->success()
            ->send();
    }

    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Save')
                ->action('save')
                ->color('primary'),
        ];
    }
}
