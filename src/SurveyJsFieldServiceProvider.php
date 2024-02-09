<?php

namespace JibayMcs\SurveyJsField;

use Filament\Support\Assets\Css;
use Filament\Support\Assets\Js;
use Filament\Support\Facades\FilamentAsset;
use JibayMcs\SurveyJsField\Forms\SurveyJSCreatorField;
use JibayMcs\SurveyJsField\Forms\SurveyJSFormField;
use Livewire\Livewire;
use Spatie\LaravelPackageTools\Commands\InstallCommand;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class SurveyJsFieldServiceProvider extends PackageServiceProvider
{
    public static string $name = 'survey-js-field';

    public static string $viewNamespace = 'survey-js-field';

    public function configurePackage(Package $package): void
    {
        $package->name(static::$name)
//            ->hasCommands($this->getCommands())
            ->hasInstallCommand(function (InstallCommand $command) {
                $command
                    ->publishConfigFile()
                    ->publishMigrations()
                    ->askToRunMigrations()
                    ->askToStarRepoOnGitHub('jibaymcs/survey-js-field');
            });

        $configFileName = $package->shortName();

        if (file_exists($package->basePath("/../config/{$configFileName}.php"))) {
            $package->hasConfigFile();
        }

        if (file_exists($package->basePath('/../database/migrations'))) {
            $package->hasMigrations($this->getMigrations());
        }

        if (file_exists($package->basePath('/../resources/lang'))) {
            $package->hasTranslations();
        }

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }
    }

    public function packageRegistered(): void
    {
        FilamentAsset::register(
            assets: [
                Css::make('survey-js-field-styles', __DIR__.'/../resources/dist/survey-js-field.css'),
                Css::make('survey-js-creator-styles', __DIR__.'/../resources/dist/survey-js-field.css')->loadedOnRequest(),

                Js::make('surveyjs-form-scripts', __DIR__.'/../resources/dist/survey-js-form.js'),
                Js::make('surveyjs-creator-scripts', __DIR__.'/../resources/dist/survey-js-creator.js'),
            ],
            package: 'jibaymcs/survey-js-field'
        );
    }

    public function packageBooted(): void
    {
        Livewire::component('surveyjs-form-field', SurveyJSFormField::class);
        Livewire::component('surveyjs-creator-field', SurveyJSCreatorField::class);

        FilamentAsset::registerScriptData(
            data: [
                'surveyjs_form_theme' => config('survey-js-field.theme'),
            ],
            package: 'jibaymcs/survey-js-field'
        );
    }

    /**
     * @return array<string>
     */
    protected function getMigrations(): array
    {
        return [
            'create_survey-js-field_table',
        ];
    }
}
