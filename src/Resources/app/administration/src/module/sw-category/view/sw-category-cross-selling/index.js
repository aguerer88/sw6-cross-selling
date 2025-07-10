import template from './sw-category-detail-cross.html.twig';

Shopware.Component.register('sw-category-detail-cross', {
    template,

    inject: ['repositoryFactory'],
    mixins: [Shopware.Mixin.getByName('notification')],

    data() {
        return {
            groups: [],
            categoryId: null,
            productCriteria: null,
            isLoading: false,
        };
    },

    computed: {
        crossSellingGroupRepository() {
            return this.repositoryFactory.create('cross_selling_product_group');
        },

        productRepository() {
            return this.repositoryFactory.create('product');
        },
    },

    created() {
        this.categoryId = this.$route.params.id;
        this.initCriteria();
        this.loadGroups();
    },

    methods: {
        initCriteria() {
            this.productCriteria = new Shopware.Data.Criteria();
            this.productCriteria.addFilter(Shopware.Data.Criteria.equals('active', true));
        },

        loadGroups() {
            const criteria = new Shopware.Data.Criteria();
            criteria.addFilter(Shopware.Data.Criteria.equals('categoryId', this.categoryId));
            criteria.addSorting(Shopware.Data.Criteria.sort('position', 'ASC'));

            this.crossSellingGroupRepository.search(criteria, Shopware.Context.api).then((result) => {
                if (Array.isArray(result)) {
                    this.groups = result;
                } else {
                    this.groups = [];
                    this.createNotificationError({
                        title: 'Fehler',
                        message: 'Unbekanntes Resultatformat bei Suche'
                    });
                }
            });
        },

        addGroup() {
            const newGroup = this.crossSellingGroupRepository.create(Shopware.Context.api);
            newGroup.categoryId = this.categoryId;
            newGroup.position = this.groups.length + 1;
            newGroup.productIds = [];

            this.groups.push(newGroup);
        },

        removeGroup(index) {
            const group = this.groups[index];
            if (group.id) {
                this.crossSellingGroupRepository.delete(group.id, Shopware.Context.api).then(() => {
                    this.groups.splice(index, 1);
                });
            } else {
                this.groups.splice(index, 1);
            }
        },

        async onSave() {
            this.isLoading = true;
            try {
                for (const group of this.groups) {
                    await this.crossSellingGroupRepository.save(group, Shopware.Context.api);
                }

                this.createNotificationSuccess({
                    title: 'Erfolg',
                    message: 'Cross Selling Produkte erfolgreich angelegt.'
                });

                this.loadGroups();

            } catch (error) {
                this.createNotificationError({
                    title: 'Fehler',
                    message: 'Die Cross Selling Produkte konnten nicht angelegt werden.'
                });
                console.error(error);
            } finally {
                this.isLoading = false;
            }
        },
    }
});
