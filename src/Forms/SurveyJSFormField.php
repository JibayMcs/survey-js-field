<?php

namespace JibayMcs\SurveyJsField\Forms;

use Closure;
use Filament\Forms\Components\Field;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use JibayMcs\SurveyJsField\Form\CheckErrorsMode;
use JibayMcs\SurveyJsField\Forms\Concerns\CanLoadAnswers;

class SurveyJSFormField extends Field
{
    use CanLoadAnswers;

    protected string $view = 'survey-js-field::forms.surveyjs-form-field';

    protected bool|Closure $isLabelHidden = true;

    public bool $showButtons = true;

    public bool $showPreviousButton = true;

    public bool $showNextButton = true;

    public bool $showCompleteButton = true;

    public bool $showRedirectBackButton = true;

    public mixed $readOnly = false;

    public bool $disableActions = false;

    public ?Closure $onCompleteSurveyClosure = null;

    public ?Closure $onSaveSurveyResponsesClosure = null;

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

    public array|Closure|null $editableFields = null;

    public Closure|bool $autoSave = true;

    public ?bool $canLoadAnswers = null;

    public string $completeButtonLabel = 'Complete';

    public string $previousButtonLabel = 'Previous';

    public string $nextButtonLabel = 'Next';

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

            if ($component->canLoadAnswers) {
                $component->mutatedFormData = $component->loadAnswers($component->getRecord(), $component->statePath, $component->mutatedFormData);
            }

            if (is_callable($component->editableFields)) {
                $component->editableFields = $component->evaluate($component->editableFields, [
                    'record' => $component->getRecord(),
                    'state' => $state,
                ]);
            }

            if (is_callable($component->autoSave)) {
                $component->autoSave = $component->evaluate($component->autoSave, [
                    'record' => $component->getRecord(),
                    'state' => $state,
                ]);
            }
        });

        $this->completeNotification();

        $this->afterStateUpdated(static function (SurveyJSFormField $component, $state): void {
            $component->callOnSaveSurveyResponses($state, $component->getRecord());
        });

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

                            if ($component->getRecord()) {
                                $component->getRecord()->update([
                                    $component->statePath => $state,
                                ]);
                            }
                        }
                    },
                ],
            ]
        );
    }

    public function getCompleteButtonLabel(): string
    {
        return $this->completeButtonLabel;
    }

    public function completeButtonLabel(string $label): static
    {
        $this->completeButtonLabel = $label;

        return $this;
    }

    public function getPreviousButtonLabel(): string
    {
        return $this->previousButtonLabel;
    }

    public function previousButtonLabel(string $label): static
    {
        $this->previousButtonLabel = $label;

        return $this;
    }

    public function getNextButtonLabel(): string
    {
        return $this->nextButtonLabel;
    }

    public function nextButtonLabel(string $label): static
    {
        $this->nextButtonLabel = $label;

        return $this;
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
     * Hide the redirect back button
     *
     * @return $this
     */
    public function hideRedirectBackButton(): static
    {
        $this->showRedirectBackButton = false;

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

    public function onSaveSurveyResponses(Closure $closure): static
    {
        $this->onSaveSurveyResponsesClosure = $closure;

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
     * Set the onSaveSurveyResponses closure when the survey responses are saved
     *
     * @return $this
     */
    public function callOnSaveSurveyResponses(mixed $state, ?Model $record): void
    {
        if ($this->onSaveSurveyResponsesClosure && ! $this->disableActions) {
            $this->evaluate($this->onSaveSurveyResponsesClosure, ['state' => $state, 'record' => $record]);
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

    /**
     * Set the editable fields for the survey
     *
     * @return $this
     */
    public function editableFields(array|Closure|null $fields): static
    {
        $this->editableFields = $fields;

        return $this;
    }

    /**
     * Set the auto save for the survey
     *
     * @return $this
     */
    public function autoSave(Closure|bool $condition = true): static
    {
        $this->autoSave = $condition;

        return $this;
    }

    /**
     * Set the can load answers for the survey
     *
     * @return $this
     */
    public function canLoadAnswers(bool $condition = true): static
    {
        $this->canLoadAnswers = $condition;

        return $this;
    }
}
