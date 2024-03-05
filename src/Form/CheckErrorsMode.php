<?php

namespace JibayMcs\SurveyJsField\Form;

enum CheckErrorsMode: string
{
    case ON_NEXT_PAGE = 'onNextPage';

    case ON_VALUE_CHANGED = 'onValueChanged';

    case ON_COMPLETE = 'onComplete';

    public static function getValues()
    {
        return [
            self::ON_VALUE_CHANGED,
            self::ON_COMPLETE,
            self::ON_NEXT_PAGE,
        ];
    }
}
