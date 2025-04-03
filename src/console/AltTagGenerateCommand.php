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

namespace doublesecretagency\sidekick\console;

use Craft;
use craft\console\Controller;
use craft\elements\Asset;
use yii\base\InvalidConfigException;
use yii\console\ExitCode;

/**
 * Class AltTagGenerateCommand
 *
 * Console command to trigger bulk generation of alt tags for assets.
 */
class AltTagGenerateCommand extends Controller
{

    /**
     * Generate alt tags for all assets in the system.
     *
     * @param string $fieldHandle The field handle where the alt tag should be stored.
     * @return int Exit code
     * @throws InvalidConfigException
     */
    public function actionGenerateAltTags(string $fieldHandle): int
    {
        // Fetch all assets in the system
        $assets = Asset::find()->all();

        if (!$assets) {
            $this->stderr("No assets found.\n");
            return ExitCode::DATAERR;
        }

        // Trigger alt tag generation
        $altTagService = Craft::$app->get('altTagService');
        $altTagService->bulkGenerateAltTags($assets, $fieldHandle);

        $this->stdout("Alt tags generated successfully.\n");
        return ExitCode::OK;
    }

}
