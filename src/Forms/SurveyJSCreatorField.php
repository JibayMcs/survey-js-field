<?php

namespace JibayMcs\SurveyJsField\Forms;

use Closure;
use Filament\Forms\Components\Field;
use Illuminate\Support\Facades\Storage;
use Livewire\WithFileUploads;

class SurveyJSCreatorField extends Field
{
    use WithFileUploads;

    protected string $view = 'survey-js-field::forms.surveyjs-creator-field';

    public ?string $licenseKey = null;

    protected bool|Closure $isLabelHidden = true;

    protected function setUp(): void
    {
        parent::setUp();

        $this->registerListeners([
            'surveyjs::uploadFiles' => [
                function ($component, $files) {
                    $component->uploadFiles($files);
                }
            ],
        ]);
    }

    public function licenseKey(?string $licenseKey = null): static
    {
        if ($licenseKey !== null)
            $this->licenseKey = $licenseKey;
        else {
            $this->licenseKey = config('survey-js-field.license_key');
        }
        return $this;
    }

    public function defaultSurvey(string $json): static
    {
        if (!str_ends_with(request()->getPathInfo(), 'edit'))
            $this->formatStateUsing(fn() => json_decode($json, true));

        return $this;
    }


    public function uploadFiles(?array $files)
    {
        if(!Storage::disk('public')->exists('surveys'))
            Storage::disk('public')->makeDirectory('surveys');

        if(Storage::move("livewire-tmp/{$files['files'][0]}", 'public/surveys/' . $files['files'][0])) {
            return response()->json(['url' => asset('storage/surveys/' . $files['files'][0])]);
        }
    }

}
