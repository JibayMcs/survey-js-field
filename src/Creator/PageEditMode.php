<?php

namespace JibayMcs\SurveyJsField\Creator;

enum PageEditMode
{
    case STANDARD;
    CASE BYPAGE;
    CASE SINGLE;

    public static function getValues()
    {
        return [
            self::STANDARD,
            self::BYPAGE,
            self::SINGLE,
        ];
    }


}
