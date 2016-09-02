<?php

/**
 *
 * @category  X-Cart 5
 * @author    Merchant e-Solutions <nrichardson@merchante-solutions.com>
 * @copyright Copyright (c) Merchant e-Solutions. All rights reserved.
 */

namespace XLite\Module\MeS\MeS\;

/**
 * Main module
 */
abstract class Main extends \XLite\Module\AModule
{
    /**
     * Author name
     *
     * @return string
     */
    public static function getAuthorName()
    {
        return 'Merchant e-Solutions';
    }

    /**
     * Module name
     *
     * @return string
     */
    public static function getModuleName()
    {
        return 'Merchant e-Solutions';
    }

    /**
     * Module description
     *
     * @return string
     */
    public static function getDescription()
    {
        return 'Enables taking credit card payments for your online store via Merchant e-Solutions\' Payment Gateway or PayHere APIs.';
    }

    /**
     * Get module major version
     *
     * @return string
     */
    public static function getMajorVersion()
    {
        return '1.0';
    }

    /**
     * Module version
     *
     * @return string
     */
    public static function getMinorVersion()
    {
        return '1';
    }

    /**
     * The module is defined as the payment module
     *
     * @return integer|null
     */
    public static function getModuleType()
    {
        return static::MODULE_TYPE_PAYMENT;
    }
}
