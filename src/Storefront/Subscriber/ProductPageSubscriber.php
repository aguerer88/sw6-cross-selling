<?php declare(strict_types=1);

namespace CrossSelling\Storefront\Subscriber;

use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;

class ProductPageSubscriber implements EventSubscriberInterface
{
    private EntityRepositoryInterface $crossSellingGroupRepository;

    public function __construct(EntityRepositoryInterface $crossSellingGroupRepository)
    {
        $this->crossSellingGroupRepository = $crossSellingGroupRepository;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            ProductPageLoadedEvent::class => 'onProductPageLoaded',
        ];
    }

    public function onProductPageLoaded(ProductPageLoadedEvent $event): void
    {
        $page = $event->getPage();
        $context = $event->getContext();
        $categoryId = $page->getNavigationId();

        if (!$categoryId) {
            return;
        }

        $criteria = new Criteria();
        $criteria->addFilter(new EqualsFilter('categoryId', $categoryId));
        $criteria->addSorting(new FieldSorting('position'));

        $groups = $this->crossSellingGroupRepository->search($criteria, $context)->getEntities();

        $page->addExtension('crossSellingGroups', $groups);
    }
}
