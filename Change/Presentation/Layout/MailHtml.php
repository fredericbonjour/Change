<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Change\Presentation\Layout;

/**
 * @name \Change\Presentation\Layout\MailHtml
 */
class MailHtml
{
	/**
	 * @var string
	 */
	protected $prefixKey = '<!-- ';

	/**
	 * @var string
	 */
	protected $suffixKey = ' -->';

	/**
	 * @var bool
	 */
	protected $fluid = true;

	/**
	 * @param string $prefixKey
	 * @return $this
	 */
	public function setPrefixKey($prefixKey)
	{
		$this->prefixKey = $prefixKey;
		return $this;
	}

	/**
	 * @param string $suffixKey
	 * @return $this
	 */
	public function setSuffixKey($suffixKey)
	{
		$this->suffixKey = $suffixKey;
		return $this;
	}

	/**
	 * @param Block $item
	 * @return null
	 */
	public function getBlockClass(\Change\Presentation\Layout\Block $item)
	{
		$vi = $item->getVisibility();
		if ($vi == 'raw')
		{
			return 'raw';
		}
		elseif ($vi)
		{
			$classes = array();
			$vi = $item->getVisibility();
			for($i = 0; $i < strlen($vi); $i++)
			{
				switch ($vi[$i])
				{
					//TODO
					case 'X' : $classes[] = 'visible-xs'; break;
					case 'S' : $classes[] = 'visible-sm'; break;
					case 'M' : $classes[] = 'visible-md'; break;
					case 'L' : $classes[] = 'visible-lg'; break;
				}
			}
			if (count($classes))
			{
				return implode(' ', $classes);
			}
		}
		return null;
	}

	/**
	 * @param \Change\Presentation\Layout\Layout $templateLayout
	 * @param \Change\Presentation\Layout\Layout $pageLayout
	 * @param Callable $callableBlockHtml
	 * @return array
	 */
	public function getHtmlParts($templateLayout, $pageLayout, $callableBlockHtml)
	{
		$prefixKey = $this->prefixKey;
		$suffixKey = $this->suffixKey;

		$twigLayout = array();
		foreach ($templateLayout->getItems() as $item)
		{
			$twigPart = null;
			if ($item instanceof Block)
			{
				$twigPart = $callableBlockHtml($item);
			}
			elseif ($item instanceof Container)
			{
				$container = $pageLayout->getById($item->getId());
				if ($container instanceof Container)
				{
					$twigPart = $this->getItemHtml($container, $callableBlockHtml);
				}
			}

			if ($twigPart)
			{
				$twigLayout[$prefixKey . $item->getId() . $suffixKey] = $twigPart;
			}
		}
		return $twigLayout;
	}

	/**
	 * @param Item $item
	 * @param Callable $callableTwigBlock
	 * @return string|null
	 */
	protected function getItemHtml($item, $callableTwigBlock)
	{
		if ($item instanceof Block)
		{
			return $callableTwigBlock($item);
		}

		$innerHTML = '';
		foreach ($item->getItems() as $childItem)
		{
			$innerHTML .= $this->getItemHtml($childItem, $callableTwigBlock);
		}
		if ($item instanceof Cell)
		{
			return '<td data-id="' . $item->getId() . '" colspan="' . $item->getSize() . '">' . $innerHTML . '</td>';
		}
		elseif ($item instanceof Row)
		{
			$class = 'row';
			return
				'<table><tr class="' . $class . '" data-id="' . $item->getId() . '" data-grid="' . $item->getGrid() . '">' . $innerHTML
				. '</tr></table>';
		}
		elseif ($item instanceof Container)
		{
			return $innerHTML;
		}
		return (!empty($innerHTML)) ? $innerHTML : null;
	}
}