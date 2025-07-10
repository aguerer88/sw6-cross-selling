import './module/sw-category/component/sw-category-view';
import './module/sw-category/view/sw-category-cross-selling';

Shopware.Module.register('sw-category-cross-selling', {
    routeMiddleware(next, currentRoute) {
        const customRouteName = 'sw.category.detail.cross';

        if (
            currentRoute.name === 'sw.category.detail' &&
            currentRoute.children.every((route) => route.name !== customRouteName)
        ) {
            currentRoute.children.push({
                name: customRouteName,
                path: 'cross-selling',
                component: 'sw-category-detail-cross',
                meta: {
                    parentPath: 'sw.category.index'
                }
            });
        }

        next(currentRoute);
    }
});