<div
    x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('survey-js-field-styles', 'jibaymcs/survey-js-field'))]"
    x-load-js="[@js(\Filament\Support\Facades\FilamentAsset::getScriptSrc('surveyjs-form-scripts', 'jibaymcs/survey-js-field'))]"
    x-init="initForm()"
    x-data="{
        surveyInstance: null,
        surveyData: $wire.entangle('survey_data'),
        isLastPage: false,

        initForm() {
            let surveyJson = {}

            if(this.surveyData) {
                surveyJson = this.surveyData
            }

            const survey = new Model(surveyJson)
            this.surveyInstance = survey;

            survey.applyTheme(filamentData.surveyjs_form_theme)
            survey.showNavigationButtons = false

            knockout.applyBindings({
                model: survey
            });

            survey.onCurrentPageChanged.add(function(sender, options) {
                if (sender.isLastPage) {
                    this.isLastPage = true;
                } else {
                    this.isLastPage = false;
                }
            }.bind(this));

            $wire.$on('surveyjs::form::previous', () => {
                console.log('previous')
                survey.prevPage();
            })

            $wire.$on('surveyjs::form::next', () => {
                console.log('next')
                survey.nextPage();
            })
        },

        next() {
            Alpine.raw(this.surveyInstance).nextPage()
        },

        previous() {
            Alpine.raw(this.surveyInstance).prevPage()
        },
    }"
>
    <survey params="survey: model" wire:ignore.self></survey>

    <div class="flex justify-between">

        <x-filament::button
            outlined
            x-on:click="previous"
        >
            Précédent
        </x-filament::button>

        <x-filament::button
            x-show="!isLastPage"
            x-on:click="next"
        >
            Suivant
        </x-filament::button>

        <x-filament::button
            x-show="isLastPage"
            x-on:click="terminate"
            color="success"
        >
            Terminer l'évaluation
        </x-filament::button>

    </div>

</div>
