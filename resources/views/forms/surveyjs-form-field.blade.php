<div
    wire:ignore
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
        nativeState: {{ $field->nativeState ? 'true' : 'false' }},
        components: @js($field->components),
        loading: true,
        editableFields: @js($field->editableFields),
        autoSave: {{ $field->autoSave ? 'true' : 'false' }},

        initForm() {

            if (this.surveyInstance) {
                // Si une instance existe déjà, la réinitialiser ou la détruire
                this.surveyInstance.dispose(); // ou méthode équivalente pour nettoyer
                this.surveyInstance = null;
            }

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

            window.DefaultLight.cssVariables['background-color'] = 'transparent';
            window.DefaultLight.cssVariables['--sjs-primary-backcolor'] = 'rgb(var(--primary-500))';

            window.DefaultDark.cssVariables['background-color'] = 'transparent';
            window.DefaultDark.cssVariables['--sjs-primary-backcolor'] = 'rgb(var(--primary-500))';

            window.addEventListener('theme-changed', (event) => {
                this.surveyInstance.applyTheme(event.detail === 'dark' ? window.DefaultDark : window.DefaultLight);
            });

            window.DefaultDark.isPanelless = this.panelless;
            window.DefaultLight.isPanelless = this.panelless;

            this.surveyInstance.applyTheme($store.theme === 'dark' ? window.DefaultDark : window.DefaultLight);

            this.surveyInstance.showNavigationButtons = false;
            this.surveyInstance.checkErrorsMode = this.checkErrorsMode;

            //if this.autoSave set auto save every 5 minutes
            if(this.autoSave) {
                setInterval(() => {

                    if(this.nativeState) {
                        this.state = this.surveyInstance.data;
                    } else {
                        this.surveyInstance.getAllQuestions().forEach(function(question) {
                            this.updateNonNativeState(this.surveyInstance, {question: question});
                        }.bind(this));
                    }

                    $wire.dispatchFormEvent('surveyjs::saveDraftData', this.state);
                    new FilamentNotification()
                        .title('Sauvegarde automatique effectuée')
                        .success()
                        .send();

                    console.log('Auto saved data');

                }, 300000);
            }

            this.surveyInstance.getAllQuestions().forEach(function(question) {
                if(this.editableFields && this.readOnly) {
                    question.readOnly = !this.editableFields.includes(question.name);
                }

                if(!this.editableFields && this.readOnly) {
                    question.readOnly = true;
                }

                question.isRequired = this.allFieldsRequired;

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
            });

            this.surveyInstance.onAfterRenderSurvey.add(function(sender, options) {
                console.info('Survey successfully rendered');

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
                if(this.editableFields && this.readOnly) {
                    options.readOnly = !this.editableFields.includes(options.name);
                }

                if(this.readOnly) {
                    return;
                }

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

            try {
                for (const key in this.mutatedFormData) {
                    if (this.mutatedFormData.hasOwnProperty(key)) {
                        const value = this.mutatedFormData[key];

                        // Assurez-vous que la valeur est correctement manipulée avant de l'utiliser
                        const rawValue = Alpine.raw(value); // Déproxyfie si nécessaire
                        const finalValue = Array.isArray(rawValue) ? rawValue[0] : rawValue;

                        this.surveyInstance.setValue(key, finalValue);
                        this.surveyInstance.validate(false);
                    }
                }
            } catch (e) { // Corrigé ici
                try {
                    this.mutatedFormData.forEach((page) => {
                        if (page.questions && Array.isArray(page.questions)) {
                            page.questions.forEach((question) => {
                                const questionName = question.element.name; // Récupérer le nom de la question
                                const rawValue = Alpine.raw(question.value); // Déproxyfier la valeur
                                const finalValue = Array.isArray(rawValue) ? rawValue[0] : rawValue; // Extraire la valeur réelle

                                console.log(`Setting value for question: ${questionName}`, finalValue);

                                // Définir la valeur dans SurveyJS
                                this.surveyInstance.setValue(questionName, finalValue);
                            });
                        }
                    });

                    // Valider après avoir défini toutes les valeurs
                    this.surveyInstance.validate(false);
                } catch (e) {
                    console.error('Error while loading survey data:', e);

                    // Si une erreur survient, faire un fallback pour éviter que l'application ne plante
                    this.surveyInstance.getAllQuestions().forEach((question) => {
                        const questionName = question.name;

                        console.log(`Fallback setting value for question: ${questionName}`);
                    });
                }
            }


            $wire.$on('surveyjs::form::previous', () => {
                this.surveyInstance.prevPage()
            });

            $wire.$on('surveyjs::form::next', () => {
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

            if(this.readOnly) {
                return;
            }

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

        redirectBack() {
            window.location.href = '{{ url()->previous() }}';
        },

        clearForm() {
            if (window.confirm('Êtes-vous sûr de vouloir effacer le formulaire ? Cette action est irréversible.')) {
                $wire.dispatchFormEvent('surveyjs::clearForm');
                this.surveyInstance.getAllQuestions().forEach(function(question) {
                    question.clearValue();
                });
            }
        }
    }"
>
    <div x-show="loading" class="flex justify-center items-center flex-col">
        <span class="font-semibold text-xl">Chargement</span>
        <x-filament::loading-indicator class="h-8 w-8" />
    </div>

    <survey params="survey: model" wire:ignore></survey>

    <template x-if="!loading">
        <div class="flex justify-between mt-4" wire:ignore>

            @if($field->showPreviousButton && $field->showButtons)
                <button style="--c-400:var(--primary-400);--c-500:var(--primary-500);--c-600:var(--primary-600);"
                        class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-primary fi-color-primary fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid fi-btn-outlined ring-1 text-custom-600 ring-custom-600 hover:bg-custom-400/10 dark:text-custom-400 dark:ring-custom-500"
                        type="button"
                        x-show="pageCount > 1"
                        @click="previous"
                >
                    <span class="fi-btn-label">{{ $field->getPreviousButtonLabel() }}</span>
                </button>
            @endif

            <button style="--c-400: var(--gray-400); --c-500: var(--gray-500); --c-600: var(--gray-600);"
                    x-show="!readOnly"
                    class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-success fi-color-success fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-custom-600 text-white hover:bg-custom-500 focus-visible:ring-custom-500/50 dark:bg-custom-500 dark:hover:bg-custom-400 dark:focus-visible:ring-custom-400/50"
                    type="button"
                    @click="clearForm"
            >
                <span class="fi-btn-label">Effacer le formulaire</span>
            </button>

            @if($field->showNextButton && $field->showButtons)
                <button style="--c-400:var(--primary-400);--c-500:var(--primary-500);--c-600:var(--primary-600);"
                        class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-primary fi-color-primary fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-custom-600 text-white hover:bg-custom-500 focus-visible:ring-custom-500/50 dark:bg-custom-500 dark:hover:bg-custom-400 dark:focus-visible:ring-custom-400/50"
                        type="button"
                        x-show="!isLastPage"
                        @click="next"
                >
                    <span class="fi-btn-label">{{ $field->getNextButtonLabel() }}</span>
                </button>
            @endif

            @if($field->showCompleteButton && $field->showButtons)
                <button style="--c-400: var(--success-400); --c-500: var(--success-500); --c-600: var(--success-600);"
                        class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-success fi-color-success fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-custom-600 text-white hover:bg-custom-500 focus-visible:ring-custom-500/50 dark:bg-custom-500 dark:hover:bg-custom-400 dark:focus-visible:ring-custom-400/50"
                        type="button"
                        x-show="isLastPage && !readOnly"
                        @click="onSurveyComplete"
                >
                    <span class="fi-btn-label">{{ $field->getCompleteButtonLabel() }}</span>
                </button>
            @endif

            @if($field->showRedirectBackButton)
                <button style="--c-400: var(--success-400); --c-500: var(--success-500); --c-600: var(--success-600);"
                        class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-success fi-color-success fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-custom-600 text-white hover:bg-custom-500 focus-visible:ring-custom-500/50 dark:bg-custom-500 dark:hover:bg-custom-400 dark:focus-visible:ring-custom-400/50"
                        type="button"
                        x-show="isLastPage && readOnly"
                        @click="redirectBack"
                >
                    <span class="fi-btn-label">Retour à la liste</span>
                </button>
            @endif
        </div>
    </template>

    @push('styles')
        <style>
            .sd-root-modern {
                background: transparent !important;
            }

            :is(.dark) {
                .sd-input {
                    background-color: #18181b !important;
                    color: #e2e8f0 !important;
                }
            }
        </style>
    @endpush
</div>
