<?php

namespace doublesecretagency\sidekick\twigextensions;

use Craft;
use craft\errors\MissingComponentException;
use doublesecretagency\sidekick\constants\AiModel;
use doublesecretagency\sidekick\constants\Session;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class SidekickTwigExtension extends AbstractExtension implements GlobalsInterface
{
    /**
     * Returns a list of global variables to add to the existing list.
     *
     * @return array
     * @throws MissingComponentException
     */
    public function getGlobals(): array
    {
        // Get the selected AI model from the session
        $selectedModel = Craft::$app->getSession()->get(Session::AI_MODEL, AiModel::DEFAULT);

        // Return global variables
        return [
            'sidekickChat' => [
                'aiModelOptions' => $this->getAiModelOptions(),
                'aiModelSelected' => $selectedModel,
            ]
        ];
    }

    /**
     * Returns the AI model options.
     *
     * @return array
     */
    private function getAiModelOptions(): array
    {
        // Initialize array for AI model options
        $aiModelOptions = [];

        // Loop through available AI models
        foreach (AiModel::AVAILABLE as $value => $label) {
            // Add each AI model to the options array
            $aiModelOptions[] = ['label' => $label, 'value' => $value];
        }

        // Return the array of AI model options
        return $aiModelOptions;
    }
}
