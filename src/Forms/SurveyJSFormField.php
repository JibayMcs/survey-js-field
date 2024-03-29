<?php

namespace JibayMcs\SurveyJsField\Forms;

use Closure;
use Filament\Forms\Components\Field;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use JibayMcs\SurveyJsField\Form\CheckErrorsMode;

class SurveyJSFormField extends Field
{
    protected string $view = 'survey-js-field::forms.surveyjs-form-field';

    protected bool|Closure $isLabelHidden = true;

    public bool $showButtons = true;

    public bool $showPreviousButton = true;

    public bool $showNextButton = true;

    public bool $showCompleteButton = true;

    public bool $readOnly = false;

    public bool $disableActions = false;

    public ?Closure $onCompleteSurveyClosure = null;

    public Notification $successNotification;

    public bool $hideQuestionNumbers = false;

    public ?bool $panelless = null;

    public ?bool $allFieldsRequired = true;

    public array|Closure|null $mutatedFormData = null;

    public string $checkErrorsMode;

    public string $locale;

    protected function setUp(): void
    {
        parent::setUp();

        $this->checkErrorsMode = CheckErrorsMode::ON_NEXT_PAGE->value;

        $this->locale = app()->getLocale();

        $this->afterStateHydrated(static function (SurveyJSFormField $component, $state): void {
            $component->mutatedFormData = $component->evaluate($component->mutatedFormData, [
                'record' => $component->getRecord(),
                'state' => $state,
            ]);
        });

        $this->completeNotification();

        $this->registerListeners(
            [
                'surveyjs::completeSurvey' => [
                    function ($component) {
                        $component->callOnCompleteSurvey($this->getState(), $component->getRecord());
                        $component->successNotification->send();
                    },
                ],
            ]
        );
    }

    public function hideNavigationButtons(): static
    {
        $this->showButtons = false;

        return $this;
    }

    public function hidePreviousButton(): static
    {
        $this->showPreviousButton = false;

        return $this;
    }

    public function hideNextButton(): static
    {
        $this->showNextButton = false;

        return $this;
    }

    public function hideCompleteButton(): static
    {
        $this->showCompleteButton = false;

        return $this;
    }

    public function readOnly(bool $condition = true): static
    {
        $this->readOnly = $condition;

        return $this;
    }

    public function disableActions(bool $condition = true): static
    {
        $this->disableActions = $condition;

        return $this;
    }

    public function onCompleteSurvey(Closure $closure): static
    {
        $this->onCompleteSurveyClosure = $closure;

        return $this;
    }

    public function callOnCompleteSurvey(mixed $state, ?Model $record): void
    {
        if ($this->onCompleteSurveyClosure && ! $this->disableActions) {
            $this->evaluate($this->onCompleteSurveyClosure, ['state' => $state, 'record' => $record]);
        }
    }

    public function completeNotification(?Notification $notification = null): static
    {
        if ($notification) {
            $this->successNotification = $notification;
        } else {
            $this->successNotification = Notification::make('success_completed_survey')
                ->success()
                ->title(__('survey-js-field::survey-js-field.notifications.success_completed_survey.title'))
                ->body(__('survey-js-field::survey-js-field.notifications.success_completed_survey.body'))
                ->icon('heroicon-o-check-circle');
        }

        return $this;
    }

    public function hideQuestionNumbers(bool $condition = true): static
    {
        $this->hideQuestionNumbers = $condition;

        return $this;
    }

    public function panelless(?bool $condition = null): static
    {
        $this->panelless = $condition;

        return $this;
    }

    public function mutateDataBeforeFillForm(array|Closure $data): static
    {
        $this->mutatedFormData = $data;

        return $this;
    }

    public function allFieldsRequired(bool $condition = true): static
    {
        $this->allFieldsRequired = $condition;

        return $this;
    }

    public function checkErrorsMode(CheckErrorsMode $mode): static
    {
        //get allowed values from enum
        $allowedValues = CheckErrorsMode::getValues();

        //check if the value is allowed
        if (! in_array($mode, $allowedValues)) {
            throw new \Exception('Invalid value for CheckErrorsMode, allowed values are: '.implode(', ', $allowedValues).'.');
        }

        $this->checkErrorsMode = $mode->value;

        return $this;
    }

    public function locale(?string $locale = null): static
    {
        $this->locale = $locale ?: app()->getLocale();

        return $this;
    }
}
