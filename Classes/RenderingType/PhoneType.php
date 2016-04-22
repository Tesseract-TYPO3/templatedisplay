<?php
namespace Tesseract\Templatedisplay\RenderingType;

/*
 * This file is part of the TYPO3 CMS project.
 *
 * It is free software; you can redistribute it and/or modify it under
 * the terms of the GNU General Public License, either version 2
 * of the License, or any later version.
 *
 * For the full copyright and license information, please read the
 * LICENSE.txt file that was distributed with this source code.
 *
 * The TYPO3 project - inspiring people to share!
 */

use Tesseract\Templatedisplay\Component\DataConsumer;
use TYPO3\CMS\Core\SingletonInterface;

/**
 * Example class for custom element types in Template Display.
 *
 * @author Francois Suter <typo3@cobweb.ch>
 * @package TYPO3
 * @subpackage tx_templatedisplay
 */
class PhoneType implements CustomTypeInterface, SingletonInterface
{
    /**
     * Renders the value in a "callto" hyperlink.
     *
     * @param mixed $value The value of the field being rendered
     * @param array $configuration TypoScript configuration for the rendering
     * @param DataConsumer $parentObject Back-reference to the calling object
     * @return string The HTML to display
     */
    public function render($value, $configuration, DataConsumer $parentObject)
    {
        $rendering = '<a href="callto://' . rawurlencode($value) . '">' . $value . '</a>';
        return $rendering;
    }
}
