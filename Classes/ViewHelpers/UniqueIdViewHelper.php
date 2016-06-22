<?php
namespace TYPO3\CMS\Wireframe\ViewHelpers;

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

use TYPO3\CMS\Core\Utility\StringUtility;
use TYPO3\CMS\Fluid\Core\ViewHelper\AbstractViewHelper;
use TYPO3Fluid\Fluid\Core\Rendering\RenderingContextInterface;

/**
 * Get unique id
 */
class UniqueIdViewHelper extends AbstractViewHelper
{

    /**
     * Generate a unique id.
     *
     * @return string
     */
    public function render()
    {
        return static::renderStatic(
            [],
            $this->buildRenderChildrenClosure(),
            $this->renderingContext
        );
    }

    /**
     * Generate a unique id.
     *
     * @param array $arguments
     * @param \Closure $renderChildrenClosure
     * @param RenderingContextInterface $renderingContext
     * @return string
     */
    public static function renderStatic(array $arguments, \Closure $renderChildrenClosure, RenderingContextInterface $renderingContext)
    {
        return StringUtility::getUniqueId();
    }
}
