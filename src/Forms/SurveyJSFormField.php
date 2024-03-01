<?php

namespace JibayMcs\SurveyJsField\Forms;

use Closure;
use Filament\Forms\Components\Field;

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

    protected function setUp(): void
    {
        parent::setUp();
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
}
