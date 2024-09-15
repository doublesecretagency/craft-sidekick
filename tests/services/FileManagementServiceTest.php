<?php

namespace doublesecretagency\sidekick\tests\services;

use doublesecretagency\sidekick\services\FileManagementService;
use PHPUnit\Framework\TestCase;
use craft\helpers\FileHelper;

/**
 * Class FileManagementServiceTest
 *
 * Tests for the FileManagementService.
 */
class FileManagementServiceTest extends TestCase
{
    private FileManagementService $fileService;
    private string $testFilePath;
    private string $testContent = '<!-- Test Twig File -->';
    private string $testDirectory = 'test-folder';

    protected function setUp(): void
    {
        parent::setUp();
        $this->fileService = new FileManagementService();
        $this->testFilePath = $this->testDirectory . DIRECTORY_SEPARATOR . 'test-file.twig';

        // Ensure the test directory exists
        $absolutePath = realpath(CRAFT_TEMPLATES_PATH) . DIRECTORY_SEPARATOR . $this->testDirectory;
        if (!is_dir($absolutePath)) {
            FileHelper::createDirectory($absolutePath);
        }
    }

    protected function tearDown(): void
    {
        // Clean up test file
        $absolutePath = realpath(CRAFT_TEMPLATES_PATH) . DIRECTORY_SEPARATOR . $this->testFilePath;
        if (file_exists($absolutePath)) {
            unlink($absolutePath);
        }

        // Remove test directory if empty
        $absoluteDir = realpath(CRAFT_TEMPLATES_PATH) . DIRECTORY_SEPARATOR . $this->testDirectory;
        if (is_dir($absoluteDir)) {
            rmdir($absoluteDir);
        }

        parent::tearDown();
    }

    public function testCreateFileSuccess()
    {
        $result = $this->fileService->createFile($this->testFilePath, $this->testContent);
        $this->assertTrue($result);

        $absolutePath = realpath(CRAFT_TEMPLATES_PATH) . DIRECTORY_SEPARATOR . $this->testFilePath;
        $this->assertFileExists($absolutePath);
        $this->assertStringContainsString($this->testContent, file_get_contents($absolutePath));
    }

    public function testCreateFileAlreadyExists()
    {
        // First creation
        $this->fileService->createFile($this->testFilePath, $this->testContent);

        // Attempt to create again
        $result = $this->fileService->createFile($this->testFilePath, $this->testContent);
        $this->assertIsString($result);
        $this->assertStringContainsString('File already exists.', $result);
    }

    public function testRewriteFileSuccess()
    {
        // Create the file first
        $this->fileService->createFile($this->testFilePath, $this->testContent);

        // New content
        $newContent = '<!-- Rewritten Twig File -->';

        // Rewrite
        $result = $this->fileService->rewriteFile($this->testFilePath, $newContent);
        $this->assertTrue($result);

        $absolutePath = realpath(CRAFT_TEMPLATES_PATH) . DIRECTORY_SEPARATOR . $this->testFilePath;
        $this->assertStringContainsString($newContent, file_get_contents($absolutePath));
    }

    public function testRewriteFileNonExistent()
    {
        // Attempt to rewrite a non-existent file
        $result = $this->fileService->rewriteFile($this->testFilePath, $this->testContent);
        $this->assertIsString($result);
        $this->assertStringContainsString('File does not exist.', $result);
    }

    public function testDeleteFileSuccess()
    {
        // Create the file first
        $this->fileService->createFile($this->testFilePath, $this->testContent);

        // Delete
        $result = $this->fileService->deleteFile($this->testFilePath);
        $this->assertTrue($result);

        $absolutePath = realpath(CRAFT_TEMPLATES_PATH) . DIRECTORY_SEPARATOR . $this->testFilePath;
        $this->assertFileDoesNotExist($absolutePath);
    }

    public function testDeleteFileNonExistent()
    {
        $result = $this->fileService->deleteFile($this->testFilePath);
        $this->assertIsString($result);
        $this->assertStringContainsString('File does not exist.', $result);
    }

    public function testIsTwigFile()
    {
        $twigFile = 'example.twig';
        $phpFile = 'example.php';

        $this->assertTrue($this->fileService->isTwigFile($twigFile));
        $this->assertFalse($this->fileService->isTwigFile($phpFile));
    }

    public function testIsPathAllowed()
    {
        $allowedPath = realpath(CRAFT_TEMPLATES_PATH) . DIRECTORY_SEPARATOR . $this->testFilePath;
        $disallowedPath = '/etc/passwd';

        $this->assertTrue($this->fileService->isPathAllowed($allowedPath));
        $this->assertFalse($this->fileService->isPathAllowed($disallowedPath));
    }

    public function testIsSecureContent()
    {
        $secureContent = '<div>Safe Content</div>';
        $insecureContent = '<?php echo "Hack"; ?>';

        $this->assertTrue($this->fileService->isSecureContent($secureContent));
        $this->assertFalse($this->fileService->isSecureContent($insecureContent));
    }

    public function testValidateTwigSyntax()
    {
        $validTwig = '<div>{{ variable }}</div>';
        $invalidTwig = '<div>{{ variable </div>';

        $this->assertTrue($this->fileService->validateTwigSyntax($validTwig));
        $this->assertFalse($this->fileService->validateTwigSyntax($invalidTwig));
    }
}
