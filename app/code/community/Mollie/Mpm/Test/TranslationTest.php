<?php

class Mollie_Mpm_Test_TranslationTest extends \PHPUnit\Framework\TestCase
{
    const GLOBAL_TRANSLATE_CALL = '/(?<![\w>:$])__\(/';

    const MAGENTO2_PLACEHOLDER = '/__\(\s*([\'"])(?:(?!\1).)*%\d/';

    public function testItDoesNotUseTheGlobalTranslateFunctionRemovedInOpenMage21()
    {
        $this->assertSame(array(), $this->findLinesMatching(self::GLOBAL_TRANSLATE_CALL));
    }

    public function testItUsesMagento1PlaceholdersInTranslations()
    {
        $this->assertSame(array(), $this->findLinesMatching(self::MAGENTO2_PLACEHOLDER));
    }

    private function findLinesMatching($pattern)
    {
        $matches = array();
        foreach ($this->getModuleFiles() as $file) {
            foreach (file($file) as $number => $line) {
                if (preg_match($pattern, $line)) {
                    $matches[] = $this->getRelativePath($file) . ':' . ($number + 1) . ': ' . trim($line);
                }
            }
        }

        return $matches;
    }

    private function getModuleFiles()
    {
        $directories = array(
            $this->getModuleRoot() . '/app/code/community/Mollie/Mpm',
            $this->getModuleRoot() . '/app/design',
        );

        $files = array();
        foreach ($directories as $directory) {
            $iterator = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($directory));
            foreach ($iterator as $file) {
                if (preg_match('/\.(php|phtml)$/', $file->getFilename()) && strpos($file->getPathname(), '/Test/') === false) {
                    $files[] = $file->getPathname();
                }
            }
        }

        return $files;
    }

    private function getRelativePath($file)
    {
        return substr($file, strlen($this->getModuleRoot()) + 1);
    }

    private function getModuleRoot()
    {
        return realpath(__DIR__ . '/../../../../../..');
    }
}
