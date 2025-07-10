<?php declare(strict_types=1);

namespace CrossSelling\Subscriber;

use CrossSelling\Core\Content\CrossSellingGroup\CrossSellingGroupEntity;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Product\ProductEntity;
use Shopware\Core\Content\ProductStream\ProductStreamEntity;
use Shopware\Core\Content\ProductStream\Service\ProductStreamBuilder;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Struct\ArrayEntity;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductPageSubscriber implements EventSubscriberInterface
{
    private EntityRepository $categoryRepo;
    private EntityRepository $crossSellingGroupRepo;
    private EntityRepository $productRepo;
    private EntityRepository $productStreamRepo;
    private ProductStreamBuilder $productStreamBuilder;

    public function __construct(
        EntityRepository $categoryRepo,
        EntityRepository $crossSellingGroupRepo,
        EntityRepository $productRepo,
        EntityRepository $productStreamRepo,
        ProductStreamBuilder $productStreamBuilder
    ) {
        $this->categoryRepo = $categoryRepo;
        $this->crossSellingGroupRepo = $crossSellingGroupRepo;
        $this->productRepo = $productRepo;
        $this->productStreamRepo = $productStreamRepo;
        $this->productStreamBuilder = $productStreamBuilder;
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
        $product = $page->getProduct();
        $context = $event->getSalesChannelContext();

        $categoryTree = $product->getCategoryTree();

        if (empty($categoryTree)) {
            return;
        }

        $startCategoryId = end($categoryTree);

        $groups = $this->loadInheritedGroups($startCategoryId, $context->getContext());
        if (!empty($groups)) {
            $page->addExtension('crossSellingGroups', new ArrayEntity($groups));
        }
    }

    private function loadInheritedGroups(string $categoryId, Context $context): array
    {
        while ($categoryId) {
            $criteria = new Criteria();
            $criteria->addFilter(new EqualsFilter('categoryId', $categoryId));
            $criteria->addSorting(new FieldSorting('position'));

            $groups = $this->crossSellingGroupRepo->search($criteria, $context)->getEntities();

            if ($groups->count() > 0) {
                $result = [];

                foreach ($groups as $group) {
                    $products = [];

                    // Manuell zugewiesene Produkte laden
                    $productIds = $group->getProductIds() ?? [];
                    if (!empty($productIds)) {
                        $productCriteria = new Criteria($productIds);
                        $productCriteria->addFilter(new EqualsFilter('active', true));
                        $productCriteria->addAssociation('cover');

                        $manualProducts = $this->productRepo->search($productCriteria, $context)->getEntities();
                        $products = array_merge($products, $manualProducts->getElements());
                    }

                    // Produkte aus ProductStream laden (dynamisch)
                    if ($group->getProductStreamId()) {
                        $productStream = $this->productStreamRepo
                            ->search(new Criteria([$group->getProductStreamId()]), $context)
                            ->get($group->getProductStreamId());

                        if ($productStream instanceof ProductStreamEntity) {
                            $filters = $this->productStreamBuilder->buildFilters(
                                $group->getProductStreamId(),
                                $context
                            );

                            $streamCriteria = new Criteria();
                            $streamCriteria->addFilter(...$filters);
                            $streamCriteria->addFilter(new EqualsFilter('active', true));
                            $streamCriteria->addAssociation('cover');
                            $streamCriteria->setLimit(10);

                            $streamProducts = $this->productRepo->search($streamCriteria, $context)->getEntities();
                            $products = array_merge($products, $streamProducts->getElements());
                        }
                    }

                    // Duplikate anhand der Produkt-IDs entfernen
                    $uniqueProducts = [];
                    $productIdsMap = [];
                    
                    foreach ($products as $product) {
                        if (!$product instanceof ProductEntity) {
                            continue;
                        }

                        $id = $product->getId();
                        if (!isset($productIdsMap[$id])) {
                            $productIdsMap[$id] = true;
                            $uniqueProducts[] = $product;
                        }
                    }

                    if (count($uniqueProducts) > 0) {
                        $group->addExtension('products', new ArrayEntity($uniqueProducts));
                        $result[] = $group;
                    }
                }

                return $result;
            }

            // Parent-Kategorie laden
            $catCriteria = new Criteria([$categoryId]);
            $category = $this->categoryRepo->search($catCriteria, $context)->first();

            if (!$category instanceof CategoryEntity || !$category->getParentId()) {
                break;
            }

            $categoryId = $category->getParentId();
        }

        return [];
    }

}
