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

namespace doublesecretagency\sidekick\skills;

use ReflectionClass;
use ReflectionMethod;

abstract class BaseSkillSet
{
    /**
     * Returns a list of methods which are restricted for any reason.
     *
     * Methods should be restricted for ongoing environmental reasons.
     *
     * For example, methods could be restricted based on:
     * - Craft version
     * - Craft edition
     * - Plugin version
     * - Environment (dev, staging, production)
     * - User permissions
     * - etc.
     *
     * @return array
     */
    protected function restrictedMethods(): array
    {
        // No restrictions by default
        return [];
    }

    /**
     * Returns a list of all available methods in the current class.
     *
     * Filters out any restricted methods defined in the `restrictedMethods()` method.
     *
     * @return array
     */
    public function getToolFunctions(): array
    {
        // Get reflection of the current class
        $reflection = new ReflectionClass($this);

        // Get all class methods which are both public and static
        $methods = array_filter(
            $reflection->getMethods(ReflectionMethod::IS_PUBLIC),
            static fn($method) => $method->isStatic()
        );

        // Get list of restricted methods
        $restrictedMethods = $this->restrictedMethods();

        // Filter out restricted methods
        $methods = array_filter($methods, static function ($method) use ($restrictedMethods) {
            return !in_array($method->getName(), $restrictedMethods, true);
        });

        // Return all available methods
        return $methods;
    }
}
