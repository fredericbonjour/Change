<?php
namespace Rbs\Review\Blocks;

use Change\Documents\Property;
use Change\Presentation\Blocks\Event;
use Change\Presentation\Blocks\Parameters;
use Change\Presentation\Blocks\Standard\Block;

/**
 * @name \Rbs\Review\Blocks\PostReview
 */
class ReviewList extends Block
{
	/**
	 * @api
	 * Set Block Parameters on $event
	 * Required Event method: getBlockLayout, getPresentationServices, getDocumentServices
	 * Optional Event method: getHttpRequest
	 * @param Event $event
	 * @return Parameters
	 */
	protected function parameterize($event)
	{
		$parameters = parent::parameterize($event);
		$parameters->addParameterMeta('showAverageRating', true);
		$parameters->addParameterMeta('averageRatingPartsCount', 5);
		$parameters->addParameterMeta('reviewsPerPage', 10);
		$parameters->addParameterMeta('targetId');

		$parameters->setLayoutParameters($event->getBlockLayout());

		if ($parameters->getParameter('targetId') === null)
		{
			$target = $event->getParam('document');
			if ($target instanceof \Change\Documents\AbstractDocument)
			{
				$parameters->setParameterValue('targetId', $target->getId());
			}
		}
		return $parameters;
	}

	/**
	 * Set $attributes and return a twig template file name OR set HtmlCallback on result
	 * Required Event method: getBlockLayout, getBlockParameters(), getBlockResult(),
	 *        getPresentationServices(), getDocumentServices()
	 * @param Event $event
	 * @param \ArrayObject $attributes
	 * @return string|null
	 */
	protected function execute($event, $attributes)
	{
		$parameters = $event->getBlockParameters();
		$target = $event->getDocumentServices()->getDocumentManager()->getDocumentInstance($parameters->getParameterValue('targetId'));
		$dqb = new \Change\Documents\Query\Query($event->getDocumentServices(), 'Rbs_Review_Review');
		//TODO add section of page to predicate?
		$dqb->andPredicates($dqb->published(), $dqb->eq('target', $target));
		//TODO add order by positive review

		$urlManager = $event->getUrlManager();
		$rows = [];
		foreach ($dqb->getDocuments() as $review)
		{
			/* @var $review \Rbs\Review\Documents\Review */
			$target = $review->getTarget();
			/* @var $target \Change\Documents\AbstractDocument|\Change\Documents\Interfaces\Publishable|\Change\Documents\Interfaces\Editable */
			$rows[] = [
				'id' => $review->getId(),
				'pseudonym' => $review->getPseudonym(),
				'rating' => $review->getRating(),
				'reviewStarRating' => ceil($review->getRating()*(5/100)),
				'reviewDate' => $review->getReviewDate(),
				'content' => $review->getContent()->getHtml(),
				'promoted' => $review->getPromoted(),
				'url' => $urlManager->getCanonicalByDocument($review, $review->getSection()->getWebsite()),
				//TODO: getLabel for target is not a good thing, find another way
				'target' => [ 'title' => $target->getLabel(), 'url' => $urlManager->getCanonicalByDocument($target, $review->getSection()->getWebsite()) ]
			];
		}
		$attributes['rows'] = $rows;

		return 'review-list.twig';
	}
}