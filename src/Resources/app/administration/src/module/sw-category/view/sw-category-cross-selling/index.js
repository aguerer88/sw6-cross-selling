import './sw-category-detail-cross.scss';
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

            isInherited: false, // zeigt an, ob von Parent geerbt wird
            hasParentCategory: false, // zeigt an, ob aktuelle Kategorie Parent hat
            isOverridingInheritedConfig: false, // zeigt an, ob Vererbung überschrieben wird
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
        // Setzt Produkt-Filter für Auswahl
        initCriteria() {
            this.productCriteria = new Shopware.Data.Criteria();
            this.productCriteria.addFilter(Shopware.Data.Criteria.equals('active', true));
        },

        // Lädt die Cross-Selling Gruppen (inkl. Vererbungslogik)
        async loadGroups(categoryId = null) {
            const id = categoryId || this.categoryId;

            const criteria = new Shopware.Data.Criteria();
            criteria.addFilter(Shopware.Data.Criteria.equals('categoryId', id));
            criteria.addSorting(Shopware.Data.Criteria.sort('position', 'ASC'));

            try {
                const result = await this.crossSellingGroupRepository.search(criteria, Shopware.Context.api);

                // Wenn es sich um die aktuelle Kategorie handelt, Parent prüfen
                if (id === this.categoryId) {
                    const categoryRepo = this.repositoryFactory.create('category');
                    const category = await categoryRepo.get(this.categoryId, Shopware.Context.api);
                    this.hasParentCategory = !!category?.parentId;
                }

                // Wenn Konfiguration vorhanden: speichern und prüfen ob vererbt
                if (result.length > 0) {
                    this.groups = result;
                    this.isInherited = (id !== this.categoryId);
                } else {
                    // Andernfalls rekursiv in Parent-Kategorien nach Konfiguration suchen
                    const categoryRepo = this.repositoryFactory.create('category');
                    const category = await categoryRepo.get(id, Shopware.Context.api);

                    if (category?.parentId) {
                        await this.loadGroups(category.parentId);
                    } else {
                        this.groups = [];
                        this.isInherited = false;
                    }
                }

                // Prüft, ob eine eigene Konfiguration trotz vorhandener Vererbung aktiv ist
                if (!this.isInherited) {
                    const inherited = await this.findFirstInheritedGroups(this.categoryId);
                    this.isOverridingInheritedConfig = inherited.length > 0;
                }

            } catch (error) {
                console.error('Fehler beim Laden der Gruppen:', error);
                this.groups = [];
                this.isInherited = false;
                this.hasParentCategory = false;
                this.isOverridingInheritedConfig = false;
            }
        },

        // Rekursive Funktion: Findet erste geerbte Gruppen (wenn vorhanden)
        async findFirstInheritedGroups(categoryId) {
            const categoryRepo = this.repositoryFactory.create('category');
            const category = await categoryRepo.get(categoryId, Shopware.Context.api);

            if (!category?.parentId) {
                return [];
            }

            const criteria = new Shopware.Data.Criteria();
            criteria.addFilter(Shopware.Data.Criteria.equals('categoryId', category.parentId));

            const result = await this.crossSellingGroupRepository.search(criteria, Shopware.Context.api);

            if (result.length > 0) {
                return result;
            }

            return this.findFirstInheritedGroups(category.parentId);
        },

        // Stellt Vererbung wieder her (löscht eigene Gruppen)
        async restoreInheritance() {
            try {
                const deleteCriteria = new Shopware.Data.Criteria();
                deleteCriteria.addFilter(Shopware.Data.Criteria.equals('categoryId', this.categoryId));

                const groupsToDelete = await this.crossSellingGroupRepository.search(deleteCriteria, Shopware.Context.api);
                const deletePromises = groupsToDelete.map(group =>
                    this.crossSellingGroupRepository.delete(group.id, Shopware.Context.api)
                );

                await Promise.all(deletePromises);

                this.groups = [];
                this.isInherited = false;
                await this.loadGroups();

            } catch (error) {
                this.createNotificationError({
                    title: 'Fehler',
                    message: 'Die Vererbung konnte nicht wiederhergestellt werden.'
                });
                console.error('Fehler beim Wiederherstellen der Vererbung:', error);
            }
        },

        // Hebt Vererbung auf und initialisiert neue Gruppe
        breakInheritance() {
            this.isInherited = false;
            this.groups = [];
            this.addGroup();
        },

        // Fügt eine neue Gruppe hinzu
        addGroup() {

            if (this.groups.length >= 2) {
                this.createNotificationWarning({
                    title: 'Limit erreicht',
                    message: 'Du kannst maximal 2 Gruppen hinzufügen.',
                });
                return;
            }

            const newGroup = this.crossSellingGroupRepository.create(Shopware.Context.api);
            newGroup.categoryId = this.categoryId;
            newGroup.position = this.groups.length + 1;
            newGroup.productIds = [];

            this.groups.push(newGroup);
        },

        // Entfernt eine Gruppe
        removeGroup(index) {
            const group = this.groups[index];

            // Prüfe, ob die Gruppe persistiert ist (aus DB kommt)
            if (!group._isNew) {
                this.crossSellingGroupRepository.delete(group.id, Shopware.Context.api).then(() => {
                    this.groups.splice(index, 1);
                }).catch(error => {
                    this.createNotificationError({
                        title: 'Fehler beim Löschen',
                        message: 'Die Gruppe konnte nicht gelöscht werden.'
                    });
                    console.error(error);
                });
            } else {
                // Noch nicht gespeichert, also nur lokal entfernen
                this.groups.splice(index, 1);
            }
        },

        // Speichert alle Gruppen
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
        }
    }
});
