<?php

namespace InfyOm\Generator\Utils;

use Illuminate\Support\Str;
use InfyOm\Generator\Common\GeneratorField;

class HTMLFieldGenerator
{
    public static function generateHTML(GeneratorField $field, $templateType): string
    {
        $viewName = $field->htmlType;
        $variables = [];

        if (!empty($validations = self::generateValidations($field))) {
            $variables['options'] = ', '.implode(', ', $validations);
        }

        switch ($field->htmlType) {
            case 'select':
            case 'enum':
                $viewName = 'select';
                $keyValues = GeneratorFieldsInputUtil::prepareKeyValueArrFromLabelValueStr($field->htmlValues);

                $variables = [
                    'selectValues' => GeneratorFieldsInputUtil::prepareKeyValueArrayStr($keyValues),
                ];
                break;
            case 'checkbox':
                if (count($field->htmlValues) > 0) {
                    $checkboxValue = $field->htmlValues[0];
                } else {
                    $checkboxValue = 1;
                }
                $variables['checkboxVal'] = $checkboxValue;
                break;
            case 'radio':
                $keyValues = GeneratorFieldsInputUtil::prepareKeyValueArrFromLabelValueStr($field->htmlValues);

                $radioButtons = [];
                foreach ($keyValues as $label => $value) {
                    $radioButtons[] = view($templateType.'.fields.radio', [
                        'label'     => $label,
                        'value'     => $value,
                        'fieldName' => $field->name,
                    ]);
                }

                return view($templateType.'.fields.radio_group', array_merge(
                    ['radioButtons' => implode(infy_nl_tab(), $radioButtons)],
                    array_merge(
                        $field->variables(),
                        $variables
                    )
                ))->render();
        }

        return view(
            $templateType.'.fields.'.$viewName,
            array_merge(
                $field->variables(),
                $variables
            )
        )->render();
    }

    public static function generateValidations(GeneratorField $field)
    {
        $validations = explode('|', $field->validations);
        $validationRules = [];

        foreach ($validations as $validation) {
            if ($validation === 'required') {
                $validationRules[] = "'required'";
                continue;
            }

            if (!Str::contains($validation, ['max:', 'min:'])) {
                continue;
            }

            $validationText = substr($validation, 0, 3);
            $sizeInNumber = substr($validation, 4);

            $sizeText = ($validationText == 'min') ? 'minlength' : 'maxlength';
            if ($field->htmlType == 'number') {
                $sizeText = $validationText;
            }

            $size = "'$sizeText' => $sizeInNumber";
            $validationRules[] = $size;
        }

        return $validationRules;
    }
    
    public static function generateCustomFieldHTML(GeneratorField $field, $templateType)
    {
        $fieldTemplate = '';

        switch ($field->htmlType) {
            case 'text':
            case 'textarea':
            case 'date':
            case 'file':
            case 'email':
            case 'password':
                $fieldTemplate = get_template('scaffold.custom_fields.' . $field->htmlType, $templateType);
                break;
            case 'number':
                $fieldTemplate = get_template('scaffold.custom_fields.' . $field->htmlType, $templateType);
                break;
            case 'select':
            case 'enum':
                if ($field->dbInput === 'hidden,mtm') {
                    $fieldTemplate = get_template('scaffold.custom_fields.selects', $templateType);
                } else {
                    $fieldTemplate = get_template('scaffold.custom_fields.select', $templateType);
                }
                if (Str::startsWith($field->htmlValues[0], '$')) {
                    $fieldTemplate = str_replace(
                        '$INPUT_ARR$',
                        $field->htmlValues[0],
                        $fieldTemplate
                    );
                    $fieldTemplate = str_replace(
                        '$INPUT_ARR_SELECTED$',
                        Str::plural($field->htmlValues[0]) . 'Selected',
                        $fieldTemplate
                    );
                } else {
                    $radioLabels = GeneratorFieldsInputUtil::prepareKeyValueArrFromLabelValueStr($field->htmlValues);

                    $fieldTemplate = str_replace(
                        '$INPUT_ARR$',
                        GeneratorFieldsInputUtil::prepareKeyValueArrayStr($radioLabels),
                        $fieldTemplate
                    );
                }
                break;
            case 'checkbox':
                $fieldTemplate = get_template('scaffold.custom_fields.checkbox', $templateType);
                if (count($field->htmlValues) > 0) {
                    $checkboxValue = $field->htmlValues[0];
                } else {
                    $checkboxValue = 1;
                }
                $fieldTemplate = str_replace('$CHECKBOX_VALUE$', $checkboxValue, $fieldTemplate);
                break;
            case 'radio':
                $fieldTemplate = get_template('scaffold.custom_fields.radio_group', $templateType);
                $radioTemplate = get_template('scaffold.custom_fields.radio', $templateType);

                $radioLabels = GeneratorFieldsInputUtil::prepareKeyValueArrFromLabelValueStr($field->htmlValues);

                $radioButtons = [];
                foreach ($radioLabels as $label => $value) {
                    $radioButtonTemplate = str_replace('$LABEL$', $label, $radioTemplate);
                    $radioButtonTemplate = str_replace('$VALUE$', $value, $radioButtonTemplate);
                    $radioButtonTemplate = str_replace('$FIELD_VALUE$', in_array($value,$field->validations)? '1': 'null', $radioButtonTemplate);
                    $radioButtons[] = $radioButtonTemplate;
                }
                $fieldTemplate = str_replace('$RADIO_BUTTONS$', implode("\n", $radioButtons), $fieldTemplate);
                break;
            case 'boolean':
                $fieldTemplate = get_template('scaffold.custom_fields.boolean', $templateType);
                break;
        }

        return $fieldTemplate;
    }
}
