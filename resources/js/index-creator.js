import { SurveyCreator } from 'survey-creator-knockout'
import { setLicenseKey, Serializer, ComponentCollection } from 'survey-core'
import { editorLocalization } from 'survey-creator-core'
import 'survey-creator-core/i18n/french'

window.setLicenseKey = setLicenseKey
window.SurveyCreator = SurveyCreator
window.editorLocalization = editorLocalization
window.Serializer = Serializer

window.registerCreatorComponents = function(components) {
    components.forEach(component => {
        ComponentCollection.Instance.add(component)
    })
}

if (window.filamentData.surveyjs.components) {
    window.filamentData.surveyjs.components.forEach(component => {
        ComponentCollection.Instance.add(component)
    })
}

window.hiddenSurveyProperties = window.filamentData.surveyjs.hiddenSurveyProperties || []

window.hiddenSurveyProperties.forEach(property => {
    Serializer.removeProperty('survey', property)
})

/**
 * Remove properties from questions/panels, etc
 * Used in the alpinejs component 'surveyjs-creator-field' on listening **onShowingProperty** event
 */
window.hiddenProperties = window.filamentData.surveyjs.hiddenProperties || []
