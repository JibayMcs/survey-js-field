<?php

namespace JibayMcs\SurveyJsField\Forms;

use Closure;
use Filament\Forms\Components\Field;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use JibayMcs\SurveyJsField\Form\CheckErrorsMode;
use JibayMcs\SurveyJsField\Forms\Concerns\CanLoadAnswers;
use Livewire\Attributes\On;

class SurveyJSFormField extends Field
{
    use CanLoadAnswers;

    protected string $view = 'survey-js-field::forms.surveyjs-form-field';

    protected bool|Closure $isLabelHidden = true;

    public bool $showButtons = true;

    public bool $showPreviousButton = true;

    public bool $showNextButton = true;

    public bool $showCompleteButton = true;

    public mixed $readOnly = false;

    public bool $disableActions = false;

    public ?Closure $onCompleteSurveyClosure = null;

    public Notification $successNotification;

    public bool $hideQuestionNumbers = false;

    public ?bool $panelless = null;

    public ?bool $allFieldsRequired = true;

    public array|Closure|null $mutatedFormData = null;

    public string $checkErrorsMode;

    public string $locale;

    public ?Closure $loadAnswersUsing = null;

    public bool $nativeState = false;

    public ?array $components = [];

    public bool $hideCompleteNotification = false;

    public ?array $draftData = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->checkErrorsMode = CheckErrorsMode::ON_NEXT_PAGE->value;

        $this->locale = app()->getLocale();

        $this->afterStateHydrated(static function (SurveyJSFormField $component, $state): void {

            if (is_callable($component->readOnly)) {
                $component->readOnly = $component->evaluate($component->readOnly, [
                    'record' => $component->getRecord(),
                    'state' => $state,
                ]);
            }

            $component->mutatedFormData = $component->evaluate($component->mutatedFormData, [
                'record' => $component->getRecord(),
                'state' => $state,
            ]);

            $component->mutatedFormData = $component->loadAnswers($component->getRecord(), $component->statePath, $component->mutatedFormData);
        });

        $this->completeNotification();

        $this->registerListeners(
            [
                'surveyjs::completeSurvey' => [
                    function (SurveyJSFormField $component) {
                        $component->callOnCompleteSurvey($this->getState(), $component->getRecord());

                        if (! $this->hideCompleteNotification) {
                            $component->successNotification->send();
                        }
                    },
                ],
                'surveyjs::saveDraftData' => [
                    function (SurveyJSFormField $component, $state) {
                        if ($component->isLive()) {
                            $component->state($state);

                            $component->getRecord()->update([
                                $component->statePath => $state,
                            ]);
                        }
                    },
                ],
            ]
        );
    }

    /**
     * Hide all the navigation buttons
     *
     * @param  bool  $condition
     * @return $this
     */
    public function hideNavigationButtons(): static
    {
        $this->showButtons = false;

        return $this;
    }

    /**
     * Hide the previous button
     *
     * @return $this
     */
    public function hidePreviousButton(): static
    {
        $this->showPreviousButton = false;

        return $this;
    }

    /**
     * Hide the next button
     *
     * @return $this
     */
    public function hideNextButton(): static
    {
        $this->showNextButton = false;

        return $this;
    }

    /**
     * Hide the complete button
     *
     * @return $this
     */
    public function hideCompleteButton(): static
    {
        $this->showCompleteButton = false;

        return $this;
    }

    /**
     * Set the survey to be read only
     *
     * @return $this
     */
    public function readOnly(bool|Closure $condition = true): static
    {
        $this->readOnly = $condition;

        return $this;
    }

    /**
     * Disable the actions for the survey
     * 'NEXT', 'PREV' and 'COMPLETE'
     *
     * @return $this
     */
    public function disableActions(bool $condition = true): static
    {
        $this->disableActions = $condition;

        return $this;
    }

    /**
     * Set the onCompleteSurvey closure when the survey is completed
     *
     * @return $this
     */
    public function onCompleteSurvey(Closure $closure): static
    {
        $this->onCompleteSurveyClosure = $closure;

        return $this;
    }

    /**
     * Call the onCompleteSurvey closure when the survey is completed
     */
    public function callOnCompleteSurvey(mixed $state, ?Model $record): void
    {
        if ($this->onCompleteSurveyClosure && ! $this->disableActions) {
            $this->evaluate($this->onCompleteSurveyClosure, ['state' => $state, 'record' => $record]);
        }
    }

    /**
     * Set the success notification for the survey
     *
     * @return $this
     */
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

    /**
     * Hide the question numbers
     *
     * @return $this
     */
    public function hideQuestionNumbers(bool $condition = true): static
    {
        $this->hideQuestionNumbers = $condition;

        return $this;
    }

    /**
     * Set the panelless mode for the survey
     *
     * @return $this
     */
    public function panelless(?bool $condition = null): static
    {
        $this->panelless = $condition;

        return $this;
    }

    /**
     * Mutate the data before filling the form
     *
     * @param  bool  $condition
     * @return $this
     */
    public function mutateDataBeforeFillForm(array|Closure $data): static
    {
        $this->mutatedFormData = $data;

        return $this;
    }

    /**
     * Set the survey to require all fields
     *
     * @return $this
     */
    public function allFieldsRequired(bool $condition = true): static
    {
        $this->allFieldsRequired = $condition;

        return $this;
    }

    /**
     * Set the check errors mode for the survey
     * allowed values are: 'ON_NEXT_PAGE', 'ON_VALUE_CHANGED' and 'ON_COMPLETE'
     *
     * @return $this
     */
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

    /**
     * Set the locale for the survey
     *
     * @return $this
     */
    public function locale(?string $locale = null): static
    {
        $this->locale = $locale ?: app()->getLocale();

        return $this;
    }

    /**
     * Set the state to be a native SurveyJS state
     *
     * @return $this
     */
    public function nativeState(bool $condition = true): static
    {
        $this->nativeState = $condition;

        return $this;
    }

    /**
     * Set custom components for the survey
     *
     * @return $this
     */
    public function components(array $components): static
    {
        $this->components = $components;

        return $this;
    }

    /**
     * Hide the complete notification
     *
     * @return $this
     */
    public function hideCompleteNotification(bool $condition = true): static
    {
        $this->hideCompleteNotification = $condition;

        return $this;
    }
}
