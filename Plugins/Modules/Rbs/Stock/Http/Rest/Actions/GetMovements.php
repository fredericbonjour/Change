<?php
/**
 * Copyright (C) 2014 Ready Business System
 *
 * This Source Code Form is subject to the terms of the Mozilla Public
 * License, v. 2.0. If a copy of the MPL was not distributed with this
 * file, You can obtain one at http://mozilla.org/MPL/2.0/.
 */
namespace Rbs\Stock\Http\Rest\Actions;

use Zend\Http\Response as HttpResponse;

/**
 * @name \Rbs\Stock\Http\Rest\Actions\GetMovements
 */
class GetMovements
{
	/**
	 * @param \Change\Http\Event $event
	 */
	public function execute($event)
	{
		$result = new \Change\Http\Rest\Result\CollectionResult();

		if (($limit = $event->getRequest()->getQuery('limit', 20)) !== null)
		{
			$result->setLimit(intval($limit));
		}

		if (($offset = $event->getRequest()->getQuery('offset', 0)) !== null)
		{
			$result->setOffset(intval($offset));
		}

		$total = 0;
		$movements = array();

		$cs = $event->getServices('commerceServices');
		if ($cs instanceof \Rbs\Commerce\CommerceServices)
		{
			$stockManager = $cs->getStockManager();

			$skuId = $event->getRequest()->getQuery('skuId');

			// Get count of total movements
			$total = $stockManager->countInventoryMovementsBySku($skuId);

			// Get list of movements
			$tmpMovements = $stockManager->getInventoryMovementsBySku($skuId, null, $limit, $offset, 'date', 'desc');

			$documentManager = $event->getApplicationServices()->getDocumentManager();
			$urlManager = $event->getUrlManager();
			$vc = new \Change\Http\Rest\ValueConverter($urlManager, $documentManager);
			foreach ($tmpMovements as $movement)
			{
				$targetId = $stockManager->getTargetIdFromTargetIdentifier($movement['target']);
				if ($targetId != null)
				{
					$targetObj = $documentManager->getDocumentInstance($targetId);
					if ($targetObj != null)
					{
						$movement['targetInstance'] = $vc->toRestValue($targetObj, \Change\Documents\Property::TYPE_DOCUMENT)->toArray();
					}
				}

				$movements[] = $movement;
			}
		}

		$result->setResources($movements);

		$result->setHttpStatusCode(HttpResponse::STATUS_CODE_200);
		$result->setCount($total);
		$event->setResult($result);
	}

} 