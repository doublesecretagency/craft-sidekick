<?php
/**
 * Sidekick plugin for Craft CMS
 *
 * Your AI companion for rapid Craft CMS development.
 *
 * @author    Double Secret Agency
 * @link      https://plugins.doublesecretagency.com/
 * @copyright Copyright (c) 2025 Double Secret Agency
 */

namespace doublesecretagency\sidekick\controllers;

use Craft;
use craft\web\Controller;
use doublesecretagency\sidekick\fields\AiSummary;
use doublesecretagency\sidekick\helpers\AiSummaryHelper;
use yii\web\BadRequestHttpException;
use yii\web\Response;

class AiSummaryController extends Controller
{
    /**
     * Generate fresh content for an AI Summary field.
     *
     * @return Response
     * @throws BadRequestHttpException
     */
    public function actionGenerate(): Response
    {
        $this->requirePostRequest();
//        $this->requireAcceptsJson();

        // Get the request service
        $request = Craft::$app->getRequest();

        // Get IDs from the request
        $fieldId = $request->getBodyParam('fieldId');
        $elementId = $request->getBodyParam('elementId');

        // Get the field and element
        $field = Craft::$app->getFields()->getFieldById($fieldId);
        $element = Craft::$app->getElements()->getElementById($elementId);

        // If the field is not found, return an error
        if (!$field) {
            return $this->asJson([
                'success' => false,
                'message' => "Field {$fieldId} not found."
            ]);
        }

        // If not an AI Summary field, return an error
        if (!$field instanceof AiSummary) {
            return $this->asJson([
                'success' => false,
                'message' => "Field {$fieldId} is not an AI Summary field."
            ]);
        }

        // If the element is not found, return an error
        if (!$element) {
            return $this->asJson([
                'success' => false,
                'message' => "Element {$elementId} not found."
            ]);
        }

        // Parse the field value
        $content = AiSummaryHelper::parseField($field, $element, true);

        // Return the AI generated content
        return $this->asJson([
            'success' => true,
            'content' => $content
        ]);
    }
}
