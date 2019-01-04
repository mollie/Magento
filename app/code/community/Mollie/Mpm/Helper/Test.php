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

class Mollie_Mpm_Helper_Test extends Mollie_Mpm_Helper_Data
{

    /**
     * @param null $testKey
     * @param null $liveKey
     *
     * @return array
     */
    public function getMethods($testKey = null, $liveKey = null)
    {
        $results = array();

        if (empty($testKey)) {
            $results[] = '<span class="mollie-error">' . $this->__('Test API-key: Empty value') . '</span>';
        } else {
            if (!preg_match('/^test_\w+$/', $testKey)) {
                $results[] = '<span class="mollie-error">' . $this->__('Test API-key: Should start with "test_"') . '</span>';
            } else {
                try {
                    $availableMethods = array();

                    $mollieApi = $this->getMollieAPI($testKey);
                    $methods = $mollieApi->methods->all(array("resource" => "orders"));

                    foreach ($methods as $apiMethod) {
                        $availableMethods[] = ucfirst($apiMethod->id);
                    }

                    if (empty($availableMethods)) {
                        $msg = $this->__('Enabled Methods: None, Please enable the payment methods in your Mollie dashboard.');
                        $methodsMsg = '<span class="enabled-methods-error">' . $msg . '</span>';
                    } else {
                        $msg = $this->__('Enabled Methods') . ': ' . implode(', ', $availableMethods);
                        $methodsMsg = '<span class="enabled-methods">' . $msg . '</span>';
                    }

                    $results[] = '<span class="mollie-success">' . $this->__('Test API-key: Success!') . $methodsMsg . '</span>';
                } catch (\Exception $e) {
                    $results[] = '<span class="mollie-error">' . $this->__(
                        'Test API-key: %s',
                        $e->getMessage()
                    ) . '</span>';
                }
            }
        }

        if (empty($liveKey)) {
            $results[] = '<span class="mollie-error">' . $this->__('Live API-key: Empty value') . '</span>';
        } else {
            if (!preg_match('/^live_\w+$/', $liveKey)) {
                $results[] = '<span class="mollie-error">' . $this->__('Live API-key: Should start with "live_"') . '</span>';
            } else {
                try {
                    $availableMethods = array();
                    $mollieApi = $this->getMollieAPI($liveKey);
                    $methods = $mollieApi->methods->all(array("resource" => "orders"));
                    foreach ($methods as $apiMethod) {
                        $availableMethods[] = ucfirst($apiMethod->id);
                    }

                    if (empty($availableMethods)) {
                        $msg = $this->__('Enabled Methods: None, Please enable the payment methods in your Mollie dashboard.');
                        $methodsMsg = '<span class="enabled-methods-error">' . $msg . '</span>';
                    } else {
                        $msg = $this->__('Enabled Methods: %s', implode(', ', $availableMethods));
                        $methodsMsg = '<span class="enabled-methods">' . $msg . '</span>';
                    }

                    $results[] = '<span class="mollie-success">' . $this->__('Live API-key: Success!') . $methodsMsg . '</span>';
                } catch (\Exception $e) {
                    $results[] = '<span class="mollie-error">' . $this->__(
                        'Live API-key: %s',
                        $e->getMessage()
                    ) . '</span>';
                }
            }
        }

        return $results;
    }

    /**
     * @return array
     */
    public function compatibilityChecker()
    {
        if (class_exists('Mollie\Api\CompatibilityChecker')) {
            $compatibilityChecker = new \Mollie\Api\CompatibilityChecker();
            if (!$compatibilityChecker->satisfiesPhpVersion()) {
                $minPhpVersion = $compatibilityChecker::MIN_PHP_VERSION;
                $msg = $this->__(
                    'Error: The client requires PHP version >= %s, you have %s.',
                    $minPhpVersion,
                    PHP_VERSION
                );
                $results[] = '<span class="mollie-error">' . $msg . '</span>';
            } else {
                $msg = $this->__('Success: PHP version: %s.', PHP_VERSION);
                $results[] = '<span class="mollie-success">' . $msg . '</span>';
            }

            if (!$compatibilityChecker->satisfiesJsonExtension()) {
                $msg = $this->__('Error: PHP extension JSON is not enabled.') . '<br/>';
                $msg .= $this->__('Please make sure to enable "json" in your PHP configuration.');
                $results[] = '<span class="mollie-error">' . $msg . '</span>';
            } else {
                $msg = $this->__('Success: JSON is enabled.');
                $results[] = '<span class="mollie-success">' . $msg . '</span>';
            }

            $minApiVersion = Mollie_Mpm_Helper_Data::MIN_API_VERSION;
            $apiVersion = Mollie\Api\MollieApiClient::CLIENT_VERSION;
            if (version_compare($minApiVersion, $apiVersion, '>=')) {
                $msg = $this->__(
                    'Error: Min requiresd Mollie API version >= %s, you have %s. ',
                    $minApiVersion,
                    $apiVersion
                );
                $msg .= $this->__('Please make sure to also update the /lib folder from the package!');
                $results[] = '<span class="mollie-error">' . $msg . '</span>';
            } else {
                $msg = $this->__('Success: Mollie API version: %s.', $apiVersion);
                $results[] = '<span class="mollie-success">' . $msg . '</span>';
            }
        } else {
            $msg = $this->__('Error: Mollie CompatibilityChecker not found.') . '<br/>';
            $results[] = '<span class="mollie-error">' . $msg . '</span>';
        }

        return $results;
    }
}