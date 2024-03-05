import { SurveyCreator } from 'survey-creator-knockout'
import { setLicenseKey, Serializer } from 'survey-core'
import {editorLocalization} from "survey-creator-core";
import "survey-creator-core/i18n/french";

window.setLicenseKey = setLicenseKey;
window.SurveyCreator = SurveyCreator;
window.editorLocalization = editorLocalization;
window.Serializer = Serializer;
