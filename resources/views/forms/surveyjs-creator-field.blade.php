<x-dynamic-component
    :component="$getFieldWrapperView()"
    :field="$field"
>
    <div
        wire:ignore
        x-load-css="[@js(\Filament\Support\Facades\FilamentAsset::getStyleHref('survey-js-creator-styles', 'jibaymcs/survey-js-field'))]"
        x-load-js="[@js(\Filament\Support\Facades\FilamentAsset::getScriptSrc('surveyjs-creator-scripts', 'jibaymcs/survey-js-field'))]"
        x-init="initCreator()"
        x-data="{
            state: $wire.$entangle('{{ $getStatePath() }}'),
            licenseKey: '{{$field->licenseKey}}',
            availableQuestionTypes: @js($field->availableQuestionTypes),
            pageEditMode: '{{ $field->pageEditMode }}',
            showSurveyTitle: {{ $field->showSurveyTitle ? 'true' : 'false'}},
            formLocale: '{{ $field->formLocale }}',
            creatorLocale: '{{ $field->creatorLocale }}',
            showJSONEditorTab: {{ $field->showJSONEditorTab ? 'true' : 'false' }},
            components: @js($field->components),
            loading: true,

            initCreator() {

                const creatorOptions = {
                    showJSONEditorTab: this.showJSONEditorTab,
                    showSurveyTitle: this.showSurveyTitle,
                    showDefaultLanguageInPreviewTab: false,
                    showTranslationTab: false,
                    defaultLanguage: this.formLocale,
                    questionTypes: this.availableQuestionTypes,
                    pageEditMode: this.pageEditMode,
                };

                editorLocalization.currentLocale = this.creatorLocale;

                if (this.licenseKey) {
                    setLicenseKey(this.licenseKey)
                }

                Serializer.findProperty('survey', 'locale').visible = false;

                window.registerCreatorComponents(this.components);

                const creator = new SurveyCreator(creatorOptions);

                creator.onShowingProperty.add(function(sender, options) {
                    let questionsType = [
                        'question',
                        'boolean',
                        'checkbox',
                        'comment',
                        'dropdown',
                        'tagbox',
                        'expression',
                        'file',
                        'html',
                        'image',
                        'imagepicker',
                        'matrix',
                        'matrixdropdown',
                        'matrixdynamic',
                        'multipletext',
                        'panel',
                        'paneldynamic',
                        'radiogroup',
                        'rating',
                        'ranking',
                        'signaturepad',
                        'text',
                    ];

                    if(questionsType.includes(options.obj.getType())) {
                        options.canShow = !window.hiddenProperties.includes(options.property.name);
                    }
                });

                this.$watch('state', (value) => {
                    // check if the value is a string
                    if (typeof value === 'string') {
                        // parse the string to a JSON object
                        creator.JSON = JSON.parse(value)
                    }

                });

                if (this.state) {
                    creator.JSON = this.state
                }

                if(creator != null) {
                    creator.render('surveyCreator');
                    this.loading = false;
                    document.getElementById('editor-section').classList.remove('hidden');
                }

                creator.onItemValueAdded.add(function(sender, options) {
                    this.state = JSON.stringify(creator.JSON);
                }.bind(this));

                creator.onModified.add(function(sender, options) {
                    this.state = JSON.stringify(creator.JSON);
                }.bind(this));

                creator.onUploadFile.add(function(_, options) {
                        $wire.uploadMultiple('files', options.files, (uploadedFilename) => {
                            $wire.dispatchFormEvent('surveyjs::uploadFiles', { files: uploadedFilename }).then(() => {
                                options.callback(
                                    'success',
                                    `/storage/surveys/${uploadedFilename}`
                                );
                            }, () => {
                                // Error callback...
                            }, (event) => {
                                // Progress callback...
                                // event.detail.progress contains a number between 1 and 100 as the upload progresses
                            }, () => {
                                // Cancelled callback...
                            });
                        }, () => {
                            // Error callback...
                        }, (event) => {
                            // Progress callback...
                            // event.detail.progress contains a number between 1 and 100 as the upload progresses
                        }, () => {
                            // Cancelled callback...
                        });
                }.bind(this));

            },
        }"
    >
        <div x-show="loading" class="flex justify-center items-center flex-col">
            <span class="font-semibold text-xl">Chargement de l'éditeur</span>
            <x-filament::loading-indicator class="h-8 w-8" />
        </div>

        <div id="editor-section"
            class="hidden fi-section rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">

            <div class="fi-section-content-ctn">
                <div class="fi-section-content p-6">
                    <div id="surveyCreator" style="height: 100vh; z-index: 1; position: sticky;"></div>
                </div>
            </div>
        </div>
    </div>
</x-dynamic-component>
