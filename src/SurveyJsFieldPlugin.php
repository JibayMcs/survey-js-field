<?php

namespace JibayMcs\SurveyJsField;

use Filament\Contracts\Plugin;
use Filament\Panel;
use Filament\Support\Facades\FilamentAsset;

class SurveyJsFieldPlugin implements Plugin
{
    public ?array $components = [];

    public ?array $hiddenProperties = [];

    public ?array $hiddenSurveyProperties = [];

    public function getId(): string
    {
        return 'survey-js-field';
    }

    public function register(Panel $panel): void
    {
        //
    }

    public function boot(Panel $panel): void
    {
        FilamentAsset::registerScriptData([
            'surveyjs' => [
                'components' => $this->components,
                'hiddenProperties' => $this->hiddenProperties,
                'hiddenSurveyProperties' => $this->hiddenSurveyProperties,
            ],
        ], package: 'jibaymcs/survey-js-field');
    }

    public static function make(): static
    {
        return app(static::class);
    }

    public static function get(): static
    {
        /** @var static $plugin */
        $plugin = filament(app(static::class)->getId());

        return $plugin;
    }

    /**
     * Register custom questions components
     */
    public function components(?array $components = []): static
    {
        $this->components = $components;

        return $this;
    }

    /**
     * Remove properties from the surveyjs question object
     */
    public function hiddenProperties(?array $hiddenProperties = []): static
    {
        $this->hiddenProperties = $hiddenProperties;

        return $this;
    }

    /**
     * Remove properties from the survey object itself
     */
    public function hiddenSurveyProperties(?array $hiddenSurveyProperties = []): static
    {
        $this->hiddenSurveyProperties = $hiddenSurveyProperties;

        return $this;
    }
}
