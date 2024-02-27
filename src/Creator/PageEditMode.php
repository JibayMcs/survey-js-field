<?php

namespace JibayMcs\SurveyJsField\Creator;

enum PageEditMode
{
    case STANDARD;
    case BYPAGE;
    case SINGLE;

    public static function getValues()
    {
        return [
            self::STANDARD,
            self::BYPAGE,
            self::SINGLE,
        ];
    }
}
