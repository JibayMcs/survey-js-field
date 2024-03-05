import { Survey } from 'survey-knockout-ui';
import 'survey-core';
import { Model, surveyLocalization } from 'survey-core';
import * as knockout from 'knockout';
import "survey-core/survey.i18n";
import "survey-core/i18n/french";
import "survey-core/i18n/english";

window.knockout = knockout;
window.Survey = Survey;
window.Model = Model;
window.surveyLocalization = surveyLocalization;
