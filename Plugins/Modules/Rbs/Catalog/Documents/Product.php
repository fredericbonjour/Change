<?php
namespace Rbs\Catalog\Documents;

use Change\Http\Rest\Result\DocumentLink;
use Change\Http\Rest\Result\DocumentResult;
use Change\Http\Rest\Result\Link;
use Change\Stdlib\String;

/**
 * @name \Rbs\Catalog\Documents\Product
 */
class Product extends \Compilation\Rbs\Catalog\Documents\Product implements \Rbs\Commerce\Interfaces\CartLineConfigCapable
{
	/**
	 * @return \Rbs\Media\Documents\Image|null
	 */
	public function getFirstVisual()
	{
		$visuals = $this->getVisuals();
		return $visuals->count() ? $visuals[0] : null;
	}

	/**
	 * @param DocumentResult $documentResult
	 */
	protected function updateRestDocumentResult($documentResult)
	{
		parent::updateRestDocumentResult($documentResult);
		$um = $documentResult->getUrlManager();
		$selfLinks = $documentResult->getRelLink('self');
		$selfLink = array_shift($selfLinks);
		if ($selfLink instanceof Link)
		{
			$pathParts = explode('/', $selfLink->getPathInfo());
			array_pop($pathParts);
			$baseUrl = implode('/', $pathParts);
			$documentResult->addLink(new Link($um, $baseUrl . '/ProductCategorization/', 'productcategorizations'));
			$documentResult->addLink(new Link($um, $baseUrl . '/Prices/', 'prices'));
			$image = $this->getFirstVisual();
			if ($image)
			{
				$documentResult->addLink(array('href' => $image->getPublicURL(512, 512), 'rel' => 'adminthumbnail'));
			}
		}

		if (is_array(($attributeValues = $documentResult->getProperty('attributeValues'))))
		{
			/* @var $product Product */
			$attributeEngine = new \Rbs\Catalog\Std\AttributeEngine($this->getDocumentServices());
			$expandedAttributeValues =  $attributeEngine->expandAttributeValues($this, $attributeValues, $documentResult->getUrlManager());
			$documentResult->setProperty('attributeValues', $expandedAttributeValues);
		}
	}

	/**
	 * @param DocumentLink $documentLink
	 * @param $extraColumn
	 */
	protected function updateRestDocumentLink($documentLink, $extraColumn)
	{
		parent::updateRestDocumentLink($documentLink, $extraColumn);

		/* @var $product \Rbs\Catalog\Documents\Product */
		$image = $this->getFirstVisual();
		if ($image)
		{
			$documentLink->setProperty('adminthumbnail',  $image->getPublicURL(512, 512));
		}
	}

	/**
	 * @param \Zend\EventManager\EventManagerInterface $eventManager
	 */
	protected function attachEvents($eventManager)
	{
		parent::attachEvents($eventManager);
		$eventManager->attach('updateRestResult', function(\Change\Documents\Events\Event $event) {
			$result = $event->getParam('restResult');
			if ($result instanceof DocumentLink)
			{

			}
		}, 5);
	}

	protected function onCreate()
	{
		if ($this->isPropertyModified('attributeValues'))
		{
			$attributeEngine = new \Rbs\Catalog\Std\AttributeEngine($this->getDocumentServices());
			$normalizedAttributeValues =  $attributeEngine->normalizeAttributeValues($this, $this->getAttributeValues());
			$this->setAttributeValues($normalizedAttributeValues);
		}
		if ($this->getNewSkuOnCreation())
		{
			$tm = $this->getApplicationServices()->getTransactionManager();

			/* @var $sku \Rbs\Stock\Documents\Sku */
			$sku = $this->getDocumentServices()->getDocumentManager()->getNewDocumentInstanceByModelName('Rbs_Stock_Sku');
			try
			{
				$tm->begin();
				$sku->setCode($this->buildSkuCodeFromLabel());
				$sku->save();
				$tm->commit();
			}
			catch (\Exception $e)
			{
				throw $tm->rollBack($e);
			}
			$this->setSku($sku);
		}
	}

	/**
	 * @return string
	 */
	protected function buildSkuCodeFromLabel()
	{
		$cs = new \Rbs\Commerce\Services\CommerceServices($this->getApplicationServices(), $this->getDocumentServices());
		$retry = 0;
		$baseCode = String::subString(preg_replace('/[^a-zA-Z0-9]+/', '-', String::stripAccents(String::toUpper($this->getLabel()))), 0, 80);
		$skuCode = $baseCode;
		$sku = $cs->getStockManager()->getSkuByCode($skuCode);
		while ($sku && $retry++ < 100)
		{
			$skuCode = String::subString($baseCode, 0, 73) . '-' . String::toUpper(String::random(6, false));
			$sku = $cs->getStockManager()->getSkuByCode($skuCode);
		}
		return $skuCode;
	}

	protected function onUpdate()
	{
		if ($this->isPropertyModified('attributeValues'))
		{
			$attributeEngine = new \Rbs\Catalog\Std\AttributeEngine($this->getDocumentServices());
			$normalizedAttributeValues =  $attributeEngine->normalizeAttributeValues($this, $this->getAttributeValues());

			//DB Stat
			$attributeEngine->setAttributeValues($this, $normalizedAttributeValues);

			$this->setAttributeValues($normalizedAttributeValues);
		}
	}

	/**
	 * @var boolean
	 */
	protected $newSkuOnCreation = true;

	/**
	 * @return boolean
	 */
	public function getNewSkuOnCreation()
	{
		return $this->newSkuOnCreation;
	}

	/**
	 * @param boolean $newSkuOnCreation
	 * @return $this
	 */
	public function setNewSkuOnCreation($newSkuOnCreation)
	{
		$this->newSkuOnCreation = $newSkuOnCreation;
		return $this;
	}

	/**
	 * @param \Rbs\Commerce\Services\CommerceServices $commerceServices
	 * @param array $parameters
	 * @return \Rbs\Catalog\Std\ProductCartLineConfig
	 */
	public function getCartLineConfig(\Rbs\Commerce\Services\CommerceServices $commerceServices, array $parameters)
	{
		$cartLineConfig = new \Rbs\Catalog\Std\ProductCartLineConfig($this);
		$options = isset($parameters['options']) ? $parameters['options'] : array();
		if (is_array($options))
		{
			foreach ($options as $optName => $optValue)
			{
				$cartLineConfig->setOption($optName, $optValue);
			}
		}
		return $cartLineConfig;
	}

	/**
	 * @param \Rbs\Commerce\Services\CommerceServices $commerceServices
	 * @param integer $webStoreId
	 * @return \Rbs\Catalog\Std\ProductPresentation
	 */
	public function getPresentation(\Rbs\Commerce\Services\CommerceServices $commerceServices, $webStoreId)
	{
		return new \Rbs\Catalog\Std\ProductPresentation($commerceServices, $this, $webStoreId);
	}

	/**
	 * @return \Change\Presentation\Interfaces\Section[]
	 */
	public function getPublicationSections()
	{
		$dqb = new \Change\Documents\Query\Query($this->getDocumentServices(), 'Rbs_Website_Section');
		$pcb = $dqb->getModelBuilder('Rbs_Catalog_Category', 'section')
			->getModelBuilder('Rbs_Catalog_ProductCategorization', 'category');
		$pb = $pcb->getPredicateBuilder();
		$pcb->andPredicates($pb->activated(), $pb->eq('product', $this));
		return $dqb->getDocuments()->toArray();
	}

	/**
	 * @param \Change\Presentation\Interfaces\Website $website
	 * @return \Change\Presentation\Interfaces\Section|null
	 */
	public function getCanonicalSection(\Change\Presentation\Interfaces\Website $website = null)
	{
		$dqb = new \Change\Documents\Query\Query($this->getDocumentServices(), 'Rbs_Website_Section');
		$pb = $dqb->getPredicateBuilder();
		if ($website)
		{
			$or = $pb->logicOr($pb->eq('id', $website->getId()), $pb->descendantOf($website->getId()));
			$dqb->andPredicates($pb->published(), $or);
		}
		else
		{
			$dqb->andPredicates($pb->published());
		}

		$cqb = $dqb->getModelBuilder('Rbs_Catalog_Category', 'section');
		$cqb->andPredicates($cqb->published());

		$pcb = $cqb->getModelBuilder('Rbs_Catalog_ProductCategorization', 'category');
		$pb = $pcb->getPredicateBuilder();
		$pcb->andPredicates($pb->activated(), $pb->eq('product', $this));

		$pcb->addOrder('canonical', false);
		return $dqb->getFirstDocument();
	}

	/**
	 * @param \Change\Presentation\Interfaces\Website $website
	 * @return \Rbs\Catalog\Documents\Category[]
	 */
	public function getPublishedCategories(\Change\Presentation\Interfaces\Website $website = null)
	{
		$cqb = new \Change\Documents\Query\Query($this->getDocumentServices(), 'Rbs_Catalog_Category');
		$cqb->andPredicates($cqb->published());

		$sqb = $cqb->getPropertyBuilder('section');
		$pb = $sqb->getPredicateBuilder();
		if ($website)
		{
			$or = $pb->logicOr($pb->eq('id', $website->getId()), $pb->descendantOf($website->getId()));
			$sqb->andPredicates($pb->published(), $or);
		}
		else
		{
			$sqb->andPredicates($pb->published());
		}

		$pqb = $cqb->getModelBuilder('Rbs_Catalog_ProductCategorization', 'category');
		$pb = $pqb->getPredicateBuilder();
		$pqb->andPredicates($pb->activated(), $pb->eq('product', $this));

		$pqb->addOrder('canonical', false);
		$cqb->addOrder('label', false);
		return $cqb->getDocuments()->toArray();
	}
}