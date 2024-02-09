<?php

namespace JibayMcs\SurveyJsField\Facades;

use Illuminate\Support\Facades\Facade;

/**
 * @see \JibayMcs\SurveyJsField\SurveyJsField
 */
class SurveyJsField extends Facade
{
    protected static function getFacadeAccessor()
    {
        return \JibayMcs\SurveyJsField\SurveyJsField::class;
    }
}
