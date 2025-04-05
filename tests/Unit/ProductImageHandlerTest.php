<?php

namespace App\Tests\Unit;

use App\Service\ProductImageHandler;
use PHPUnit\Framework\MockObject\Exception;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\String\UnicodeString;

class ProductImageHandlerTest extends TestCase
{
    private string $uploadDir;

    protected function setUp(): void
    {
        $this->uploadDir = sys_get_temp_dir() . '/product_uploads_test'; // create path in /tmp
        if (!file_exists($this->uploadDir)) {
            mkdir($this->uploadDir, 0777, true); // create the folder if it doesn't exist
        }
    }

    protected function tearDown(): void
    {
        array_map('unlink', glob("$this->uploadDir/*")); // delete all files in the folder
        rmdir($this->uploadDir); // remove the folder itself
    }

    // Test that files are correctly uploaded and stored

    /**
     * @throws Exception
     */
    public function testProcessUploadsAddsFiles(): void
    {
        // Mock the SluggerInterface to return a predictable "safe" filename
        $slugger = $this->createMock(SluggerInterface::class);
        $slugger->method('slug')->willReturn(new UnicodeString('filename'));

        // Instantiate the handler with mocked slugger and temp dir
        $handler = new ProductImageHandler($this->uploadDir, $slugger);

        // Create a temp file to simulate an uploaded file
        $tempFile = tempnam(sys_get_temp_dir(), 'upload');
        file_put_contents($tempFile, 'dummy content'); // put some content in the temp file

        // Simulate an UploadedFile for testing
        $uploadedFile = new UploadedFile(
            $tempFile,
            'Test Image.jpg', // original name
            null,
            null,
            true // test mode skips some checks
        );

        // Call the method under test
        $result = $handler->processUploads([$uploadedFile]);

        // Assert that one image was added
        $this->assertCount(1, $result);

        // Assert that the file actually exists in the upload dir
        $this->assertFileExists($this->uploadDir . '/' . $result[0]);
    }

    /**
     * @throws Exception
     */
    // Test that files are correctly removed from the filesystem and image list
    public function testProcessRemovalsDeletesFiles(): void
    {
        // Create mock slugger (not used here but required for constructor)
        $slugger = $this->createMock(SluggerInterface::class);
        $handler = new ProductImageHandler($this->uploadDir, $slugger);

        // Manually create a file in the upload directory
        $filename = 'to_delete.jpg';
        $filepath = $this->uploadDir . '/' . $filename;
        file_put_contents($filepath, 'test content');

        // Call the method under test
        $result = $handler->processRemovals([$filename], [$filename]);

        // Assert that the file is deleted
        $this->assertFileDoesNotExist($filepath);

        // Assert that the filename is removed from the array
        $this->assertEmpty($result);
    }
}
