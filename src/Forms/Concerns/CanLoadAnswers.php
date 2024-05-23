<?php

namespace JibayMcs\SurveyJsField\Forms\Concerns;

trait CanLoadAnswers
{
    public function loadAnswers($record, $statePath, $mutatedFormData)
    {
        return array_merge($mutatedFormData, $this->extractQuestionsWithValues($record->{$statePath}));
    }

    private function extractQuestionsWithValues(?array $pages = null)
    {
        $questions = [];

        if(!$pages) {
            return $questions;
        }

        foreach ($pages as $page) {
            if (isset($page['questions'])) {
                $this->extractElementsWithValues($page['questions'], $questions);
            }
        }

        return $questions;
    }

    private function extractElementsWithValues(array $elements, array &$questions)
    {
        foreach ($elements as $element) {
            if ($element['element']['type'] === 'panel' && isset($element['questions'])) {
                // Parcourt les éléments du panel
                $this->extractElementsWithValues($element['questions'], $questions);
            } elseif ($element['element']['type'] !== 'panel') {
                if (strpos($element['element']['type'], 'matrix') !== false) {
                    // Si le type contient "matrix", on formate la valeur en array primaire avec des arrays de valeurs
                    $questions[$element['element']['name']] = $element['value'] ?? [];
                } else {
                    // Ajoute la question et sa valeur au tableau des questions
                    $questions[$element['element']['name']] = $element['value'][0] ?? null;
                }
            }
        }
    }
}
