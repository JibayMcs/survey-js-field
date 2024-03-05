<div
    x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('survey-js-field-styles', 'jibaymcs/survey-js-field'))]"
    x-load-js="[@js(\Filament\Support\Facades\FilamentAsset::getScriptSrc('surveyjs-form-scripts', 'jibaymcs/survey-js-field'))]"
    x-init="initForm()"
    x-data="{
        surveyInstance: null,
        state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$getStatePath()}')") }},
        isLastPage: false,
        readOnly: {{ $field->readOnly ? 'true' : 'false' }},
        disableActions: {{ $field->disableActions ? 'true' : 'false' }},
        hideQuestionNumbers: {{ $field->hideQuestionNumbers ? 'true' : 'false' }},
        panelless: {{ $field->panelless !== null ? $field->panelless ? 'true' : 'false' : 'undefined' }},
        mutatedFormData: @js($field->mutatedFormData),
        allFieldsRequired: {{ $field->allFieldsRequired ? 'true' : 'false' }},
        checkErrorsMode: '{{ $field->checkErrorsMode }}',
        locale: '{{ $field->locale }}',

        initForm() {
            let surveyJson = Alpine.raw(this.state);

            if(this.state) {
                surveyJson = Alpine.raw(this.state);
                this.state = [];
            }

            this.surveyInstance = new window.Model(surveyJson);

            this.surveyInstance.locale = this.locale;

            window.surveyLocalization.locales['fr'].requiredError = 'Ce champ est obligatoire';
            window.surveyLocalization.locales['en'].requiredError = 'This field is required';

            let theme = filamentData.surveyjs_form_theme;

            if(this.panelless) {
                theme.isPanelless = this.panelless;
            }

            this.surveyInstance.applyTheme(theme);
            this.surveyInstance.showNavigationButtons = false;
            this.surveyInstance.checkErrorsMode = this.checkErrorsMode;

            for (const key in this.mutatedFormData) {
                if (this.mutatedFormData.hasOwnProperty(key)) {
                    const value = this.mutatedFormData[key];
                    this.surveyInstance.setValue(key, value);
                }
            }


            this.surveyInstance.getAllQuestions().forEach(function(question) {
                question.readOnly = this.readOnly;

                if(this.allFieldsRequired) {
                    question.isRequired = true;
                }

                if(this.disableActions) {
                    question.isRequired = false;
                }

                if(this.hideQuestionNumbers) {
                    question.hideNumber = this.hideQuestionNumbers;
                }

            }.bind(this))

            window.knockout.applyBindings({
                model: this.surveyInstance,
            })

            this.surveyInstance.onCurrentPageChanged.add(function(sender, options) {
                if (sender.isLastPage) {
                    this.isLastPage = true
                } else {
                    this.isLastPage = false
                }
            }.bind(this))

            this.surveyInstance.onValueChanged.add(function(sender, options) {

                if(this.checkErrorsMode === 'onValueChanged') {
                    sender.validate();
                }

                // Récupère la question qui a changé
                const question = options.question

                let checkedValues = question.getType() === 'checkbox' ? question.getPlainData().data.map((item) => item.displayValue) : sender.data[question.name] // Valeurs cochées

                if (!Array.isArray(checkedValues)) {
                    checkedValues = checkedValues ? [checkedValues] : []
                }

                // Initialisation de l'objet de réponse
                let response = {
                    type: question.getType(),
                    name: question.name,
                    title: question.title,
                    value: checkedValues, // Valeurs sélectionnées pour tous les types de questions
                }

                if (question.description) {
                    console.log(question.description)
                    response.description = question.description
                }

                if (question.getType() === 'checkbox') {

                    // Pour les questions de type checkbox, déterminer les valeurs non sélectionnées
                    const uncheckedValues = question.choices
                    .filter(choice => !checkedValues.includes(choice.text))
                    .map(choice => choice.text)

                    // Ajouter les valeurs non cochées à l'objet de réponse
                    response.unchecked = uncheckedValues
                }

                if (question.getType() === 'boolean') {
                    response.trueLabel = question.trueLabel || 'Yes' // Utilisez des valeurs par défaut ou celles fournies par SurveyJS
                    response.falseLabel = question.falseLabel || 'No'
                }

                // Trouve l'index de l'objet de réponse existant dans this.state (s'il existe)
                const existingIndex = this.state.findIndex(item => item.name === question.name)

                // Remplace l'objet existant par le nouvel objet ou l'ajoute s'il n'existe pas
                if (existingIndex !== -1) {
                    this.state[existingIndex] = response
                } else {
                    this.state.push(response)
                }

                // Logique supplémentaire si nécessaire, par exemple, mettre à jour le composant Livewire
                // window.Livewire.emit('updateState', this.state);
            }.bind(this))


            $wire.$on('surveyjs::form::previous', () => {
                console.log('previous')
                this.surveyInstance.prevPage()
            })

            $wire.$on('surveyjs::form::next', () => {
                console.log('next')
                this.surveyInstance.nextPage()
            })
        },

        next() {
            Alpine.raw(this.surveyInstance).nextPage()
        },

        previous() {
            Alpine.raw(this.surveyInstance).prevPage()
        },

        onSurveyComplete() {

            let validated = true;

            if(this.checkErrorsMode === 'onComplete') {
                validated = this.surveyInstance.validate();
            }

            if(validated) {
                $wire.dispatchFormEvent('surveyjs::completeSurvey');
            }
        },

    }"
>
    <survey params="survey: model" wire:ignore></survey>

    <div class="flex justify-between">

        @if($field->showPreviousButton && $field->showButtons)
            <x-filament::button
                outlined
                x-on:click="previous"
            >
                Précédent
            </x-filament::button>
        @endif

        @if($field->showNextButton && $field->showButtons)
            <x-filament::button
                x-show="!isLastPage"
                x-on:click="next"
            >
                Suivant
            </x-filament::button>
        @endif

        @if($field->showCompleteButton && $field->showButtons)
            <x-filament::button
                x-show="isLastPage"
                x-on:click="onSurveyComplete"
                color="success"
            >
                Terminer l'évaluation
            </x-filament::button>
        @endif
    </div>

</div>
