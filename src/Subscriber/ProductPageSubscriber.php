<?php declare(strict_types=1);

namespace CrossSelling\Subscriber;

use CrossSelling\Core\Content\CrossSellingGroup\CrossSellingGroupEntity;
use Shopware\Core\Content\Category\CategoryEntity;
use Shopware\Core\Content\Product\SalesChannel\Listing\ProductListingLoader;
use Shopware\Core\Content\ProductStream\ProductStreamEntity;
use Shopware\Core\Content\ProductStream\Service\ProductStreamBuilder;
use Shopware\Core\Framework\Context;
use Shopware\Core\Framework\DataAbstractionLayer\EntityRepository;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Criteria;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Filter\EqualsFilter;
use Shopware\Core\Framework\DataAbstractionLayer\Search\Sorting\FieldSorting;
use Shopware\Core\Framework\Struct\ArrayEntity;
use Shopware\Core\System\SystemConfig\SystemConfigService;
use Shopware\Storefront\Page\Product\ProductPageLoadedEvent;
use Shopware\Core\System\SalesChannel\SalesChannelContext;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class ProductPageSubscriber implements EventSubscriberInterface
{
    private EntityRepository $categoryRepo;
    private EntityRepository $crossSellingGroupRepo;
    private EntityRepository $productStreamRepo;
    private ProductStreamBuilder $productStreamBuilder;
    private ProductListingLoader $productListingLoader;
    private SystemConfigService $systemConfigService;

    public function __construct(
        EntityRepository $categoryRepo,
        EntityRepository $crossSellingGroupRepo,
        EntityRepository $productStreamRepo,
        ProductStreamBuilder $productStreamBuilder,
        ProductListingLoader $productListingLoader,
        SystemConfigService $systemConfigService
    ) {
        $this->categoryRepo = $categoryRepo;
        $this->crossSellingGroupRepo = $crossSellingGroupRepo;
        $this->productStreamRepo = $productStreamRepo;
        $this->productStreamBuilder = $productStreamBuilder;
        $this->productListingLoader = $productListingLoader;
        $this->systemConfigService = $systemConfigService;
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
        $salesChannelContext = $event->getSalesChannelContext();

        $categoryTree = $product->getCategoryTree();

        if (empty($categoryTree)) {
            return;
        }

        $startCategoryId = end($categoryTree);
        $showBelowImages = (bool) $this->systemConfigService->get('CrossSellingProducts.config.showBelowImages') ?? true;
        $limit = (int) $this->systemConfigService->get('CrossSellingProducts.config.maxProductsPerStream') ?? 10;

        $groups = $this->loadInheritedGroups($startCategoryId, $salesChannelContext);
        
        if (!empty($groups)) {
            $page->addExtension('crossSellingGroups', new ArrayEntity($groups));
            $page->addExtension('crossSellingConfig', new ArrayEntity([
                'showBelowImages' => $showBelowImages,
                'maxProductsPerStream' => $limit
            ]));
        }
    }

    private function loadInheritedGroups(string $categoryId, SalesChannelContext $salesChannelContext): array
    {
        $context = $salesChannelContext->getContext();

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
                        $productCriteria->setLimit(50);

                        $manualProductsResult = $this->productListingLoader->load($productCriteria, $salesChannelContext);
                        $manualProducts = $manualProductsResult->getEntities()->getElements();
                        $products = array_merge($products, $manualProducts);
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
                            $streamCriteria->addSorting(new FieldSorting('id', FieldSorting::ASCENDING, true));
                            $streamCriteria->addAssociation('cover');
                            $streamCriteria->setLimit(50);

                            $streamProductsResult = $this->productListingLoader->load($streamCriteria, $salesChannelContext);
                            $streamProducts = $streamProductsResult->getEntities()->getElements();

                            shuffle($streamProducts);

                            $products = array_merge($products, $streamProducts);
                        }
                    }

                    // Duplikate anhand der Produkt-IDs entfernen
                    $uniqueProducts = [];
                    $productIdsMap = [];
                    
                    foreach ($products as $product) {
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
