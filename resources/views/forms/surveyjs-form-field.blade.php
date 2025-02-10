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

            //let theme = filamentData.surveyjs_form_theme;

            if(this.panelless) {
                theme.isPanelless = this.panelless;
            }

            window.addEventListener('theme-changed', (event) => {
                this.surveyInstance.applyTheme(event.detail === 'dark' ? window.DefaultDark : window.DefaultLight);
            });

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

            for (const key in this.mutatedFormData) {
                if (this.mutatedFormData.hasOwnProperty(key)) {
                    const value = this.mutatedFormData[key];
                    this.surveyInstance.setValue(key, value);
                    this.surveyInstance.validate(false);
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
  // Récupère la question qui a changé et le nom de la page courante
  const question = options.question;
  const pageName = sender.currentPage.jsonObj.name;

  let checkedValues;

  // Récupération de la valeur en fonction du type de question
  if (question.getType() === 'checkbox') {
    // Pour les checkbox, on récupère les valeurs cochées dans un tableau
    checkedValues = question.getPlainData().data.map(item => item.displayValue);
  } else if (question.getType() === 'matrixdropdown') {
    // Pour une question matrixdropdown, la valeur est attendue comme un objet.
    // Dans vos données, elle est stockée comme un tableau contenant un unique objet, on le déballera.
    checkedValues = sender.data[question.name];
    if (Array.isArray(checkedValues) &&
        checkedValues.length === 1 &&
        typeof checkedValues[0] === 'object') {
      checkedValues = checkedValues[0];
    }
  } else {
    // Pour les autres types de questions, on s'assure d'avoir un tableau de valeurs
    checkedValues = sender.data[question.name];
    if (!Array.isArray(checkedValues)) {
      checkedValues = checkedValues ? [checkedValues] : [];
    }
  }

  // Fonction auxiliaire pour créer l'objet réponse
  const createResponse = (question) => {
    let response = {
      element: {
        type: question.getType(),
        name: question.name,
        title: question.title,
        inputType: question.inputType || undefined
      },
      value: checkedValues
    };

    if (question.description) {
      response.description = question.description;
    }

    if (question.startWithNewLine) {
      response.element.startWithNewLine = true;
    }

    if (question.getType() === 'checkbox') {
      // Pour les checkbox, on détermine les valeurs non cochées
      const uncheckedValues = question.choices
        .filter(choice => !checkedValues.includes(choice.text))
        .map(choice => choice.text);
      response.unchecked = uncheckedValues;
    }

    if (question.getType() === 'boolean') {
      response.trueLabel = question.trueLabel || 'Yes';
      response.falseLabel = question.falseLabel || 'No';
    }

    // Pour matrixdropdown, on s'assure que la valeur est déjà un objet (et non un tableau)
    if (question.getType() === 'matrixdropdown') {
      response.value = checkedValues;
    }

    return response;
  };

  // Création de l'objet réponse pour la question
  let response = createResponse(question);

  // Initialisation de la structure de données pour la page
  let data = {
    page: pageName,
    questions: []
  };

  // Recherche de la page existante dans l'état
  const pageIndex = this.state.findIndex(item => item.page === pageName);

  if (question.parent && question.parent.getType() === 'panel') {
    // Si la question est dans un panel
    const panel = question.parent;
    let panelQuestion = null;

    if (pageIndex !== -1) {
      panelQuestion = this.state[pageIndex].questions.find(q => q.element.name === panel.name);
    }

    if (!panelQuestion) {
      // Création du panel s'il n'existe pas
      panelQuestion = {
        element: {
          type: panel.getType(),
          name: panel.name,
          title: panel.title
        },
        questions: []
      };

      if (panel.startWithNewLine) {
        panelQuestion.element.startWithNewLine = true;
      }

      if (pageIndex !== -1) {
        this.state[pageIndex].questions.push(panelQuestion);
      } else {
        data.questions.push(panelQuestion);
        this.state.push(data);
      }
    }

    // Mise à jour ou ajout de la question dans le panel
    const existingQuestionIndex = panelQuestion.questions.findIndex(q => q.element.name === question.name);
    if (existingQuestionIndex !== -1) {
      panelQuestion.questions[existingQuestionIndex] = response;
    } else {
      panelQuestion.questions.push(response);
    }
  } else {
    // Si la question n'appartient pas à un panel
    if (pageIndex !== -1) {
      const questionIndex = this.state[pageIndex].questions.findIndex(item => item.element.name === question.name);
      if (questionIndex !== -1) {
        this.state[pageIndex].questions[questionIndex] = response;
      } else {
        this.state[pageIndex].questions.push(response);
      }
    } else {
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
        }

    }"
>
    <div x-show="loading" class="flex justify-center items-center flex-col">
        <span class="font-semibold text-xl">Chargement de l'évaluation</span>
        <x-filament::loading-indicator class="h-8 w-8" />
    </div>

    <survey params="survey: model" wire:ignore></survey>

    <template x-if="!loading">
        <div class="flex justify-between" wire:ignore>

            @if($field->showPreviousButton && $field->showButtons)
                <button style="--c-400:var(--primary-400);--c-500:var(--primary-500);--c-600:var(--primary-600);"
                        class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-primary fi-color-primary fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid fi-btn-outlined ring-1 text-custom-600 ring-custom-600 hover:bg-custom-400/10 dark:text-custom-400 dark:ring-custom-500"
                        type="button"
                        x-show="pageCount > 1"
                        @click="previous"
                >
                    <span class="fi-btn-label">Précédent</span>
                </button>
            @endif

            @if($field->showNextButton && $field->showButtons)
                <button style="--c-400:var(--primary-400);--c-500:var(--primary-500);--c-600:var(--primary-600);"
                        class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-primary fi-color-primary fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-custom-600 text-white hover:bg-custom-500 focus-visible:ring-custom-500/50 dark:bg-custom-500 dark:hover:bg-custom-400 dark:focus-visible:ring-custom-400/50"
                        type="button"
                        x-show="!isLastPage"
                        @click="next"
                >
                    <span class="fi-btn-label">Suivant</span>
                </button>
            @endif

            @if($field->showCompleteButton && $field->showButtons)
                <button style="--c-400: var(--success-400); --c-500: var(--success-500); --c-600: var(--success-600);"
                        class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-success fi-color-success fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-custom-600 text-white hover:bg-custom-500 focus-visible:ring-custom-500/50 dark:bg-custom-500 dark:hover:bg-custom-400 dark:focus-visible:ring-custom-400/50"
                        type="button"
                        x-show="isLastPage && !readOnly"
                        @click="onSurveyComplete"
                >
                    <span class="fi-btn-label">Terminer l'évaluation</span>
                </button>
            @endif

            <button style="--c-400: var(--success-400); --c-500: var(--success-500); --c-600: var(--success-600);"
                    class="fi-btn relative grid-flow-col items-center justify-center font-semibold outline-none transition duration-75 focus-visible:ring-2 rounded-lg fi-color-custom fi-btn-color-success fi-color-success fi-size-md fi-btn-size-md gap-1.5 px-3 py-2 text-sm inline-grid shadow-sm bg-custom-600 text-white hover:bg-custom-500 focus-visible:ring-custom-500/50 dark:bg-custom-500 dark:hover:bg-custom-400 dark:focus-visible:ring-custom-400/50"
                    type="button"
                    x-show="isLastPage && readOnly"
                    @click="redirectBack"
            >
                <span class="fi-btn-label">Retour à la liste</span>
            </button>
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
