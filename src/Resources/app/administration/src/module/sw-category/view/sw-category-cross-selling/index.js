import template from './sw-category-detail-cross.html.twig';

Shopware.Component.register('sw-category-detail-cross', {
    template,

    inject: ['repositoryFactory'],

    data() {
        return {
            groups: [],
            isLoading: true,
            groupRepository: null,
        };
    },

    computed: {
        categoryId() {
            return this.$route.params.id;
        }
    },
});