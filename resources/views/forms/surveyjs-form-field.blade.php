<div
    x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('survey-js-field-styles', 'jibaymcs/survey-js-field'))]"
    x-load-js="[@js(\Filament\Support\Facades\FilamentAsset::getScriptSrc('surveyjs-form-scripts', 'jibaymcs/survey-js-field'))]"
    x-init="initForm()"
    x-data="{
        surveyInstance: null,
        state: $wire.{{ $applyStateBindingModifiers("\$entangle('{$getStatePath()}')") }},
        isLastPage: false,
        pageCount: -1,
        readOnly: {{ $field->readOnly ? 'true' : 'false' }},
        disableActions: {{ $field->disableActions ? 'true' : 'false' }},
        hideQuestionNumbers: {{ $field->hideQuestionNumbers ? 'true' : 'false' }},
        panelless: {{ $field->panelless !== null ? $field->panelless ? 'true' : 'false' : 'undefined' }},
        mutatedFormData: @js($field->mutatedFormData),
        allFieldsRequired: {{ $field->allFieldsRequired ? 'true' : 'false' }},
        checkErrorsMode: '{{ $field->checkErrorsMode }}',
        locale: '{{ $field->locale }}',
        answerData: @js($field->answerData ?? []),
        nativeState: {{ $field->nativeState ? 'true' : 'false' }},
        components: @js($field->components),
        loading: true,

        initForm() {
            let surveyJson = Alpine.raw(this.state);

            if(this.state) {
                surveyJson = Alpine.raw(this.state);
                this.state = [];
            }

            window.registerFormComponents(this.components);

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

                if(question.getType() === 'signaturepad') {
                    question.penColor = 'black';
                    question.backgroundColor = 'transparent';
                }

            }.bind(this))

            window.knockout.applyBindings({
                model: this.surveyInstance,
            })

            this.surveyInstance.onAfterRenderSurvey.add(function(sender, options) {
                if(this.pageCount === -1) {
                    this.pageCount = sender.visiblePages.length;
                }

                if (sender.isLastPage) {
                    this.isLastPage = true
                } else {
                    this.isLastPage = false
                }

                this.loading = false;
            }.bind(this))

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

                if(this.nativeState) {
                    this.state = sender.data;
                } else {
                    this.updateNonNativeState(sender, options);
                }

                // Logique supplémentaire si nécessaire, par exemple, mettre à jour le composant Livewire
                $wire.dispatchFormEvent('surveyjs::saveDraftData', this.state);
            }.bind(this));

            for (const key in this.mutatedFormData) {
                if (this.mutatedFormData.hasOwnProperty(key)) {
                    const value = this.mutatedFormData[key];
                    this.surveyInstance.setValue(key, value);
                    this.surveyInstance.validate(false);
                }
            }

            $wire.$on('surveyjs::form::previous', () => {
                console.log('previous')
                this.surveyInstance.prevPage()
            });

            $wire.$on('surveyjs::form::next', () => {
                console.log('next')
                this.surveyInstance.nextPage()
            });
        },

        updateNonNativeState(sender, options) {
            // Récupère la question qui a changé
            const question = options.question;
            const pageName = sender.currentPage.jsonObj.name;

            let checkedValues = question.getType() === 'checkbox' ? question.getPlainData().data.map((item) => item.displayValue) : sender.data[question.name]; // Valeurs cochées

            if (!Array.isArray(checkedValues)) {
                checkedValues = checkedValues ? [checkedValues] : [];
            }

            // Fonction auxiliaire pour créer une réponse avec startWithNewLine
            const createResponse = (question) => {
                let response = {
                    element: {
                        type: question.getType(),
                        name: question.name,
                        title: question.title,
                        inputType: question.inputType || undefined // Ajout de l'inputType pour les questions de type 'text'
                    },
                    value: checkedValues,
                };

                if (question.description) {
                    response.description = question.description;
                }

                // Ajout de startWithNewLine si vrai
                if (question.startWithNewLine) {
                    response.element.startWithNewLine = true;
                }

                if (question.getType() === 'checkbox') {
                    // Pour les questions de type checkbox, déterminer les valeurs non sélectionnées
                    const uncheckedValues = question.choices
                        .filter(choice => !checkedValues.includes(choice.text))
                        .map(choice => choice.text);

                    // Ajouter les valeurs non cochées à l'objet de réponse
                    response.unchecked = uncheckedValues;
                }

                if (question.getType() === 'boolean') {
                    response.trueLabel = question.trueLabel || 'Yes'; // Utilisez des valeurs par défaut ou celles fournies par SurveyJS
                    response.falseLabel = question.falseLabel || 'No';
                }

                return response;
            };

            let response = createResponse(question);

            // Initialisation de la structure de données
            let data = {
                page: pageName,
                questions: []
            };

            // Trouve l'index de la page existante dans this.state (s'il existe)
            const pageIndex = this.state.findIndex(item => item.page === pageName);

            if (question.parent && question.parent.getType() === 'panel') {
                // Gestion des questions de type panel
                const panel = question.parent;

                // Cherche si le panel existe déjà dans les questions de la page
                let panelQuestion = null;
                if (pageIndex !== -1) {
                    panelQuestion = this.state[pageIndex].questions.find(q => q.element.name === panel.name);
                }

                if (!panelQuestion) {
                    // Si le panel n'existe pas, on le crée
                    panelQuestion = {
                        element: {
                            type: panel.getType(),
                            name: panel.name,
                            title: panel.title
                        },
                        questions: []
                    };

                    // Ajout de startWithNewLine pour le panel si vrai
                    if (panel.startWithNewLine) {
                        panelQuestion.element.startWithNewLine = true;
                    }

                    if (pageIndex !== -1) {
                        // Si la page existe déjà, on ajoute le panel à cette page
                        this.state[pageIndex].questions.push(panelQuestion);
                    } else {
                        // Si la page n'existe pas, on la crée et on y ajoute le panel
                        data.questions.push(panelQuestion);
                        this.state.push(data);
                    }
                }

                // Vérifie si la question existe déjà dans le panel
                const existingQuestionIndex = panelQuestion.questions.findIndex(q => q.element.name === question.name);
                if (existingQuestionIndex !== -1) {
                    // Met à jour la question existante dans le panel
                    panelQuestion.questions[existingQuestionIndex] = response;
                } else {
                    // Ajoute la nouvelle question au panel
                    panelQuestion.questions.push(response);
                }

            } else {
                // Si la question n'appartient pas à un panel
                if (pageIndex !== -1) {
                    // Si la page existe déjà, on trouve l'index de la question
                    const questionIndex = this.state[pageIndex].questions.findIndex(item => item.element.name === question.name);

                    if (questionIndex !== -1) {
                        // Si la question existe déjà, on met à jour la réponse
                        this.state[pageIndex].questions[questionIndex] = response;
                    } else {
                        // Sinon, on ajoute la nouvelle question à la page existante
                        this.state[pageIndex].questions.push(response);
                    }
                } else {
                    // Si la page n'existe pas, on l'ajoute à this.state
                    data.questions.push(response);
                    this.state.push(data);
                }
            }
        },

        next() {
            Alpine.raw(this.surveyInstance).nextPage()
        },

        previous() {
            Alpine.raw(this.surveyInstance).prevPage()
        },

        onSurveyComplete() {

            let validated = true;

            if(this.allFieldsRequired) {
                validated = this.surveyInstance.validate();
            }

            if(this.checkErrorsMode === 'onComplete') {
                validated = this.surveyInstance.validate();
            }

            if(validated) {
                $wire.dispatchFormEvent('surveyjs::completeSurvey');
            }
        },

    }"
>
    <div x-show="loading" class="flex justify-center items-center flex-col">
        <span class="font-semibold text-xl">Chargement de l'évaluation</span>
        <x-filament::loading-indicator class="h-5 w-5" />
    </div>

    <survey params="survey: model" wire:ignore></survey>


    <template x-if="!loading">
        <div class="flex justify-between" wire:ignore>

            @if($field->showPreviousButton && $field->showButtons)
                <x-filament::button
                    x-show="pageCount > 1"
                    outlined
                    @click="previous"
                >
                    Précédent
                </x-filament::button>
            @endif

            @if($field->showNextButton && $field->showButtons)
                <x-filament::button
                    x-show="!isLastPage"
                    @click="next"
                >
                    Suivant
                </x-filament::button>
            @endif

            @if($field->showCompleteButton && $field->showButtons)
                <x-filament::button
                    x-show="isLastPage"
                    @click="onSurveyComplete"
                    color="success"
                >
                    Terminer l'évaluation
                </x-filament::button>
            @endif
        </div>
    </template>

</div>
