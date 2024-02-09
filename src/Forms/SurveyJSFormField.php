<?php

namespace JibayMcs\SurveyJsField\Forms;

use Closure;
use Filament\Forms\Components\Field;

class SurveyJSFormField extends Field
{
    protected string $view = 'survey-js-field::forms.surveyjs-form-field';

    protected bool|Closure $isLabelHidden = true;

    protected function setUp(): void
    {
        parent::setUp();
    }
}
