<?php

namespace App\Filament\Pages;

use App\Domains\Language\Models\Language;
use App\Settings\GeneralBlogSettings;
use Filament\Actions\Action;
use Filament\Forms\Components\RichEditor;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Pages\Concerns\InteractsWithFormActions;
use Filament\Pages\Page;

class BlogSettingsPage extends Page
{
    use InteractsWithFormActions;
    protected static ?string $navigationGroup = 'Content';

    protected static ?string $navigationIcon = 'heroicon-o-cog-6-tooth';

    protected static ?string $navigationLabel = 'Blog Settings';

    protected static string $view = 'filament.pages.blog-settings-page';

    /** @var array<string, mixed>|null */
    public ?array $data = [];

    public function mount(): void
    {
        $settings = app(GeneralBlogSettings::class);
        $languages = Language::orderByDesc('is_default')->orderBy('name')->get();

        $formData = [
            'articles_per_page' => $settings->articles_per_page,
        ];

        foreach ($languages as $language) {
            $code = $language->code;

            $formData["blog_title_{$code}"] = $settings->blog_title[$code] ?? '';
            $formData["blog_description_{$code}"] = $settings->blog_description[$code] ?? '';
            $formData["meta_title_{$code}"] = $settings->meta_title[$code] ?? '';
            $formData["meta_description_{$code}"] = $settings->meta_description[$code] ?? '';
            $formData["meta_keywords_{$code}"] = $settings->meta_keywords[$code] ?? '';
            $formData["slug_{$code}"] = $settings->slug[$code] ?? '';
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
                    TextInput::make("blog_title_{$code}")
                        ->label('Blog Title')
                        ->maxLength(255)
                        ->nullable(),

                    RichEditor::make("blog_description_{$code}")
                        ->label('Blog Description')
                        ->nullable()
                        ->columnSpanFull(),

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

                    TextInput::make("slug_{$code}")
                        ->label('Slug')
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

                Section::make('General')
                    ->schema([
                        TextInput::make('articles_per_page')
                            ->label('Articles Per Page')
                            ->numeric()
                            ->default(10)
                            ->minValue(1)
                            ->required(),
                    ]),
            ])
            ->statePath('data');
    }

    public function save(): void
    {
        $data = $this->form->getState();

        $settings = app(GeneralBlogSettings::class);
        $languages = Language::orderByDesc('is_default')->orderBy('name')->get();

        foreach ($languages as $language) {
            $code = $language->code;

            $settings->blog_title[$code] = $data["blog_title_{$code}"] ?? '';
            $settings->blog_description[$code] = $data["blog_description_{$code}"] ?? '';
            $settings->meta_title[$code] = $data["meta_title_{$code}"] ?? '';
            $settings->meta_description[$code] = $data["meta_description_{$code}"] ?? '';
            $settings->meta_keywords[$code] = $data["meta_keywords_{$code}"] ?? '';
            $settings->slug[$code] = $data["slug_{$code}"] ?? '';
        }

        $settings->articles_per_page = (int) ($data['articles_per_page'] ?? 10);

        $settings->save();

        Notification::make()
            ->title('Blog settings saved successfully.')
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
