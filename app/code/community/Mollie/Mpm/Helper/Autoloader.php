<?php
/**
 * Copyright (c) 2012-2019, Mollie B.V.
 * All rights reserved.
 *
 * Redistribution and use in source and binary forms, with or without
 * modification, are permitted provided that the following conditions are met:
 *
 * - Redistributions of source code must retain the above copyright notice,
 *   this list of conditions and the following disclaimer.
 * - Redistributions in binary form must reproduce the above copyright notice,
 *   this list of conditions and the following disclaimer in the documentation
 *   and/or other materials provided with the distribution.
 *
 * THIS SOFTWARE IS PROVIDED BY THE AUTHOR AND CONTRIBUTORS ``AS IS'' AND ANY
 * EXPRESS OR IMPLIED WARRANTIES, INCLUDING, BUT NOT LIMITED TO, THE IMPLIED
 * WARRANTIES OF MERCHANTABILITY AND FITNESS FOR A PARTICULAR PURPOSE ARE
 * DISCLAIMED. IN NO EVENT SHALL THE AUTHOR OR CONTRIBUTORS BE LIABLE FOR ANY
 * DIRECT, INDIRECT, INCIDENTAL, SPECIAL, EXEMPLARY, OR CONSEQUENTIAL DAMAGES
 * (INCLUDING, BUT NOT LIMITED TO, PROCUREMENT OF SUBSTITUTE GOODS OR
 * SERVICES; LOSS OF USE, DATA, OR PROFITS; OR BUSINESS INTERRUPTION) HOWEVER
 * CAUSED AND ON ANY THEORY OF LIABILITY, WHETHER IN CONTRACT, STRICT
 * LIABILITY, OR TORT (INCLUDING NEGLIGENCE OR OTHERWISE) ARISING IN ANY WAY
 * OUT OF THE USE OF THIS SOFTWARE, EVEN IF ADVISED OF THE POSSIBILITY OF SUCH
 * DAMAGE.
 *
 * @category    Mollie
 * @package     Mollie_Mpm
 * @author      Mollie B.V. (info@mollie.nl)
 * @copyright   Copyright (c) 2012-2019 Mollie B.V. (https://www.mollie.nl)
 * @license     http://www.opensource.org/licenses/bsd-license.php  BSD-License 2
 */

/**
 * PSR-4 Autoloader, based on
 * https://www.integer-net.com/magento-1-magento-2-using-advanced-autoloading/
 * https://github.com/integer-net/solr-magento1/blob/master/src/app/code/community/IntegerNet/Solr/Helper/Autoloader.php
 */
class Mollie_Mpm_Helper_Autoloader
{

    /**
     * @var array
     */
    protected $prefixes = array();

    /**
     *
     */
    public static function createAndRegister()
    {
        if (self::_getStoreConfig('payment/mollie/register_autoloader')) {
            $libBaseDir = self::_getStoreConfig('payment/mollie/autoloader_basepath');
            if ($libBaseDir[0] !== '/') {
                $libBaseDir = Mage::getBaseDir() . DS . $libBaseDir;
            }

            self::createAndRegisterWithBaseDir($libBaseDir);
        }
    }

    /**
     * Load store config first in case we are in update mode, where store config would not be available
     *
     * @param $path
     *
     * @return bool
     */
    protected static function _getStoreConfig($path)
    {
        static $configLoaded = false;
        if (!$configLoaded && Mage::app()->getUpdateMode()) {
            Mage::getConfig()->loadDb();
            $configLoaded = true;
        }

        return Mage::getStoreConfig($path);
    }

    /**
     * @param $libBaseDir
     */
    public static function createAndRegisterWithBaseDir($libBaseDir)
    {
        static $registered = false;
        if (!$registered) {
            $autoloader = new self;
            $autoloader
                ->addNamespace('Psr\Http\Message', $libBaseDir . '/Psr/HttpMessage/src')
                ->addNamespace('Mollie\Api', $libBaseDir . '/Mollie/src')
                ->addNamespace('GuzzleHttp', $libBaseDir . '/GuzzleHttp/Guzzle/src')
                ->addNamespace('GuzzleHttp\Psr7', $libBaseDir . '/GuzzleHttp/Psr7/src')
                ->addNamespace('GuzzleHttp\Promise', $libBaseDir . '/GuzzleHttp/Promises/src')
                ->addNamespace('Composer\CaBundle', $libBaseDir . '/Composer/CaBundle/src')
                ->register();
            $registered = true;
            self::loadGuzzleFunctions($libBaseDir);
        }

    }

    /**
     * Register loader with SPL autoloader stack.
     *
     * @return void
     */
    public function register()
    {
        spl_autoload_register(array($this, 'loadClass'), true, true);
    }

    /**
     * Adds a base directory for a namespace prefix.
     *
     * @param string $prefix   The namespace prefix.
     * @param string $baseDir A base directory for class files in the
     *                         namespace.
     * @param bool   $prepend  If true, prepend the base directory to the stack
     *                         instead of appending it; this causes it to be searched first rather
     *                         than last.
     *
     * @return $this
     */
    public function addNamespace($prefix, $baseDir, $prepend = false)
    {
        $prefix = trim($prefix, '\\') . '\\';
        $baseDir = rtrim($baseDir, DIRECTORY_SEPARATOR) . '/';

        if (isset($this->prefixes[$prefix]) === false) {
            $this->prefixes[$prefix] = array();
        }

        if ($prepend) {
            array_unshift($this->prefixes[$prefix], $baseDir);
        } else {
            array_push($this->prefixes[$prefix], $baseDir);
        }

        return $this;
    }

    /**
     * @param $libBaseDir
     */
    public static function loadGuzzleFunctions($libBaseDir)
    {
        if (file_exists($libBaseDir . '/GuzzleHttp/Guzzle/src/functions_include.php')) {
            require_once $libBaseDir . '/GuzzleHttp/Guzzle/src/functions_include.php';
        }

        if (file_exists($libBaseDir . '/GuzzleHttp/Psr7/src/functions_include.php')) {
            require_once $libBaseDir . '/GuzzleHttp/Psr7/src/functions_include.php';
        }

        if (file_exists($libBaseDir . '/GuzzleHttp/Promises/src/functions_include.php')) {
            require_once $libBaseDir . '/GuzzleHttp/Promises/src/functions_include.php';
        }
    }

    /**
     * Loads the class file for a given class name.
     *
     * @param string $class The fully-qualified class name.
     *
     * @return mixed The mapped file name on success, or boolean false on
     * failure.
     */
    public function loadClass($class)
    {
        if (strpos($class, '\\') === false) {
            $class = str_replace('_', '\\', $class);
        }
        
        $prefix = $class;
        
        while (false !== $pos = strrpos($prefix, '\\')) {
            $prefix = substr($class, 0, $pos + 1);
            $relativeClass = substr($class, $pos + 1);
            $mappedFile = $this->loadMappedFile($prefix, $relativeClass);
            if ($mappedFile) {
                return $mappedFile;
            }

            $prefix = rtrim($prefix, '\\');
        }

        return false;
    }

    /**
     * Load the mapped file for a namespace prefix and relative class.
     *
     * @param string $prefix         The namespace prefix.
     * @param string $relativeClass The relative class name.
     *
     * @return mixed Boolean false if no mapped file can be loaded, or the
     * name of the mapped file that was loaded.
     */
    protected function loadMappedFile($prefix, $relativeClass)
    {
        if (isset($this->prefixes[$prefix]) === false) {
            return false;
        }

        foreach ($this->prefixes[$prefix] as $baseDir) {
            $file = $baseDir . str_replace('\\', '/', $relativeClass) . '.php';
            if ($this->requireFile($file)) {
                return $file;
            }
        }

        return false;
    }

    /**
     * If a file exists, require it from the file system.
     *
     * @param string $file The file to require.
     *
     * @return bool True if the file exists, false if not.
     */
    protected function requireFile($file)
    {
        if (file_exists($file)) {
            require $file;
            return true;
        }

        return false;
    }
}
