<?php

/** @noinspection PhpDocSignatureInspection */
/** @noinspection PhpDocMissingThrowsInspection */

namespace doublesecretagency\sidekick\helpers;

class ApiTools
{
    /**
     * Create a new file with specified content.
     *
     * @param string $directory Directory to create the file in.
     * @param string $file Name of the file to create.
     * @param string $content Content to include in the file.
     * @return array
     */
    public static function createFile(array $args): array
    {
        // Get parameters
        $directory = $args['directory'] ?? null;
        $file      = $args['file']      ?? null;
        $content   = $args['content']   ?? null;

        // TEMP
        if (random_int(0, 4)) {
            return [
                'success' => true,
                'message' => "Successfully created {$directory}/{$file}"
            ];
        } else {
            return [
                'success' => false,
                'message' => "Unable to create file {$directory}/{$file}."
            ];
        }
        // ENDTEMP

    }

    /**
     * Delete the specified file.
     *
     * @param string $directory The directory of the file to be deleted.
     * @param string $file The name of the file to be deleted.
     * @return array
     */
    public static function deleteFile(array $args): array
    {
        // Get parameters
        $directory = $args['directory'] ?? null;
        $file      = $args['file']      ?? null;

        // TEMP
        if (random_int(0, 4)) {
            return [
                'success' => true,
                'message' => "Successfully deleted {$directory}/{$file}"
            ];
        } else {
            return [
                'success' => false,
                'message' => "Unable to delete file {$directory}/{$file}."
            ];
        }
        // ENDTEMP

    }
}
