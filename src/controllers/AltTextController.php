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
use craft\elements\Asset;
use craft\errors\ElementNotFoundException;
use craft\web\Controller;
use doublesecretagency\sidekick\services\OpenAIService;
use Throwable;
use yii\web\BadRequestHttpException;
use yii\web\Response;
use yii\base\Exception;

/**
 * Class AltTextController
 *
 * Controller for handling alt text generation via OpenAI.
 */
class AltTextController extends Controller
{

    /**
     * Generate alt text for a given asset.
     *
     * @return Response
     * @throws Exception
     * @throws Throwable
     * @throws ElementNotFoundException
     * @throws BadRequestHttpException
     */
    public function actionGenerate(): Response
    {
        $this->requirePostRequest();
//        $this->requireAcceptsJson();

        // Get asset ID from the request
        $assetId = Craft::$app->getRequest()->getBodyParam('assetId');

        // If no asset ID provided
        if (!$assetId) {
            return $this->asJson([
                'success' => false,
                'message' => 'Asset ID missing.'
            ]);
        }

        // Fetch the asset
        $asset = Asset::find()->id($assetId)->one();

        // If asset not found
        if (!$asset) {
            return $this->asJson([
                'success' => false,
                'message' => 'Asset not found.'
            ]);
        }

        try {
            // Get an instance of OpenAIService
            $openAIService = new OpenAIService();

            // Generate alt text using OpenAI
            $generatedAltText = $openAIService->generateAltText($asset);

        } catch (Exception $e) {
            // Error generating alt text
            return $this->asJson([
                'success' => false,
                'message' => 'Failed to generate alt text: ' . $e->getMessage()
            ]);
        }

        // Update the asset's alt text field
        $asset->alt = $generatedAltText;

        // If asset couldn't be saved
        if (!Craft::$app->getElements()->saveElement($asset)) {
            return $this->asJson([
                'success' => false,
                'message' => 'Failed to save asset.'
            ]);
        }

        // Return success response
        return $this->asJson([
            'success' => true,
            'altText' => $generatedAltText
        ]);
    }

}
