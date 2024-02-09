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

            initCreator() {
                const creatorOptions = {
                    showJSONEditorTab: false,
                    showSurveyTitle: true,
                    showDefaultLanguageInPreviewTab: false,
                    defaultLanguage: 'fr',
                };

                editorLocalization.currentLocale = 'fr';

                if (this.licenseKey) {
                    setLicenseKey(this.licenseKey)
                }

                const creator = new SurveyCreator(creatorOptions);

                if (this.state) {
                    creator.JSON = this.state
                }

                if(creator != null) {
                    creator.render('surveyCreator');
                }

                creator.onModified.add(function(sender, options) {
                    this.state = sender.JSON
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
                });

            },
        }"
    >
        <x-filament::card>
            <div id="surveyCreator" style="height: 100vh;"></div>
        </x-filament::card>
    </div>
</x-dynamic-component>
