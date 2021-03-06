<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Brand\Blocks;

use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Rbs\Catalog\Blocks\Product
 */
class Brand extends Block
{
	/**
	 * Event Params 'website', 'document', 'page'
	 * @api
	 * Set Block Parameters on $event
	 * @param Event $event
	 * @return Parameters
	 */
	protected function parameterize($event)
	{
		$parameters = parent::parameterize($event);
		$parameters->addParameterMeta(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME);

		$parameters->setLayoutParameters($event->getBlockLayout());

		$parameters = $this->setParameterValueForDetailBlock($parameters, $event);

		return $parameters;
	}

	/**
	 * @param \Change\Documents\AbstractDocument $document
	 * @return boolean
	 */
	protected function isValidDocument($document)
	{
		return $document instanceof \Rbs\Brand\Documents\Brand && $document->published();
	}

	/**
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * @param Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		$parameters = $event->getBlockParameters();
		$brandId = $parameters->getParameter(static::DOCUMENT_TO_DISPLAY_PROPERTY_NAME);
		if ($brandId)
		{
			$documentManager = $event->getApplicationServices()->getDocumentManager();

			$brand = $documentManager->getDocumentInstance($brandId);
			if ($brand instanceof \Rbs\Brand\Documents\Brand)
			{
				$attributes['visual'] = $brand->getVisual() ? $brand->getVisual()->getPublicURL(540, 405) : null;
				$attributes['websiteURL'] = $brand->getCurrentLocalization()->getWebsiteUrl();
				$attributes['description'] = $brand->getCurrentLocalization()->getDescription();

				return 'brand.twig';
			}
		}
		return null;
	}


}