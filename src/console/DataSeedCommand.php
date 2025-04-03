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
use yii\base\InvalidConfigException;
use yii\console\ExitCode;

/**
 * Class DataSeedCommand
 *
 * Console command to trigger seeding of dummy data into sections.
 */
class DataSeedCommand extends Controller
{

    /**
     * Seed dummy data into a specified section.
     *
     * @param string $sectionHandle The handle of the section to seed with dummy data.
     * @param int $numEntries The number of dummy entries to generate.
     * @return int Exit code
     * @throws InvalidConfigException
     */
    public function actionSeedDummyData(string $sectionHandle, int $numEntries = 5): int
    {
        // Fetch the section by handle
        $section = Craft::$app->sections->getSectionByHandle($sectionHandle);

        if (!$section) {
            $this->stderr("Section with handle '{$sectionHandle}' not found.\n");
            return ExitCode::DATAERR;
        }

        // Trigger dummy data seeding
        $dummyDataService = Craft::$app->get('dummyDataService');
        if ($dummyDataService->seedDummyData($section, $numEntries)) {
            $this->stdout("Successfully seeded {$numEntries} dummy entries into section '{$section->name}'.\n");
            return ExitCode::OK;
        }

        $this->stderr("Failed to seed dummy data into section '{$section->name}'.\n");
        return ExitCode::UNSPECIFIED_ERROR;
    }

}
