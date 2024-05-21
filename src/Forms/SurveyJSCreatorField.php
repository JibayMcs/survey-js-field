<?php

namespace JibayMcs\SurveyJsField\Forms;

use Closure;
use Filament\Forms\Components\Field;
use Illuminate\Support\Facades\Storage;
use JibayMcs\SurveyJsField\Creator\PageEditMode;
use Livewire\WithFileUploads;

class SurveyJSCreatorField extends Field
{
    use WithFileUploads;

    protected string $view = 'survey-js-field::forms.surveyjs-creator-field';

    public ?string $licenseKey = null;

    protected bool|Closure $isLabelHidden = true;

    public ?array $availableQuestionTypes = [];

    public string $pageEditMode;

    public bool $showSurveyTitle = true;

    public string $formLocale;

    public string $creatorLocale;

    public ?bool $showJSONEditorTab = false;

    public ?array $components = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->pageEditMode = PageEditMode::STANDARD->name;

        $this->formLocale = app()->getLocale();
        $this->creatorLocale = app()->getLocale();

        $this->registerListeners([
            'surveyjs::uploadFiles' => [
                function ($component, $files) {
                    $component->uploadFiles($files);
                },
            ],
        ]);
    }

    public function licenseKey(?string $licenseKey = null): static
    {
        if ($licenseKey !== null) {
            $this->licenseKey = $licenseKey;
        } else {
            $this->licenseKey = config('survey-js-field.license_key');
        }

        return $this;
    }

    public function defaultSurvey(string $json): static
    {
        if (! str_ends_with(request()->getPathInfo(), 'edit')) {
            $this->formatStateUsing(fn () => json_decode($json, true));
        }

        return $this;
    }

    public function uploadFiles(?array $files)
    {
        if (! Storage::disk('public')->exists('surveys')) {
            Storage::disk('public')->makeDirectory('surveys');
        }

        if (Storage::move("livewire-tmp/{$files['files'][0]}", 'public/surveys/'.$files['files'][0])) {
            return response()->json(['url' => asset('storage/surveys/'.$files['files'][0])]);
        }
    }

    public function availableQuestionTypes(array $questionTypes): static
    {
        $this->availableQuestionTypes = $questionTypes;

        return $this;
    }

    public function pageEditMode(PageEditMode $editMode): static
    {
        //get allowed values from enum
        $allowedValues = PageEditMode::getValues();

        //check if the value is allowed
        if (! in_array($editMode, $allowedValues)) {
            throw new \Exception('Invalid value for PageEditMode, allowed values are: '.implode(', ', $allowedValues).'.');
        }

        $this->pageEditMode = strtolower($editMode->name);

        return $this;
    }

    public function showSurveyTitle(bool $condition = true): static
    {
        $this->showSurveyTitle = $condition;

        return $this;
    }

    public function formLocale(?string $locale = null): static
    {
        $this->formLocale = $locale ?: app()->getLocale();

        return $this;
    }

    public function creatorLocale(?string $locale = null): static
    {
        $this->creatorLocale = $locale ?: app()->getLocale();

        return $this;
    }

    public function showJSONEditorTab(bool $condition = true): static
    {
        $this->showJSONEditorTab = $condition;

        return $this;
    }

    public function components(array $components): static
    {
        $this->components = $components;
        return $this;
    }
}
