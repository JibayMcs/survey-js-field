<?php

namespace JibayMcs\SurveyJsField\Commands;

use Illuminate\Console\Command;

class SurveyJsFieldCommand extends Command
{
    public $signature = 'survey-js-field';

    public $description = 'My command';

    public function handle(): int
    {
        $this->comment('All done');

        return self::SUCCESS;
    }
}
