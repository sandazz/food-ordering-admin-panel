<?php
namespace App\Utils;

class UIStrings
{
    protected static array $map = [
        'en' => [
            // Navigation
            'nav.role.admin' => 'Admin',
            'nav.role.branch_admin' => 'Branch Admin',
            'nav.role.restaurant_admin' => 'Restaurant Admin',
            'nav.dashboard' => 'Dashboard',
            'nav.orders' => 'Orders',
            'nav.menu' => 'Menu',
            'nav.menu_sizes' => 'Menu Sizes',
            'nav.menu_bases' => 'Menu Bases',
            'nav.staff' => 'Staff',
            'nav.reports' => 'Reports',
            'nav.notifications' => 'Notifications',
            'nav.settings' => 'Settings',
            'nav.logout' => 'Logout',

            // Branch selector
            'branches.all' => 'All Branches',
            'branches.clear' => 'Clear',
            'branch' => 'Branch',

            // Common
            'common.name' => 'Name',
            'common.price' => 'Price',
            'common.available' => 'Available',
            'common.active' => 'Active',
            'common.yes' => 'Yes',
            'common.no' => 'No',
            'common.edit' => 'Edit',
            'common.delete' => 'Delete',
            'common.add' => 'Add',
            'common.create' => 'Create',
            'common.update' => 'Update',
            'common.save' => 'Save',
            'common.cancel' => 'Cancel',
            'common.action' => 'Action',

            // Field labels
            'field.restaurant' => 'Restaurant',
            'field.branch' => 'Branch',
            'field.name_en' => 'Name (English)',
            'field.name_fi' => 'Name (Finnish)',
            'field.description_en' => 'Description (English)',
            'field.description_fi' => 'Description (Finnish)',
            'field.default_price' => 'Default Price',
            'field.display_order' => 'Display Order',
            'field.item_price_optional' => 'Item Price (optional)',
            'field.available' => 'Available',
            'field.image_url' => 'Image URL',
            'field.sizes_select' => 'Sizes (select one or more)',
            'field.bases_select' => 'Bases (select one or more)',
            'select.restaurant' => 'Select restaurant',
            'placeholder.price_override_optional' => 'Price (override optional)',
            'placeholder.item_price_hint' => 'Leave empty to auto-sum size+base',

            // Form titles
            'sizes.edit.title' => 'Edit Size',
            'bases.create.title' => 'Add Base',
            'bases.edit.title' => 'Edit Base',
            'categories.create.title' => 'Create Category',
            'categories.edit.title' => 'Edit Category',
            'items.create.title' => 'Add Item',
            'items.edit.title' => 'Edit Item',

            // Menu index
            'menu.index.title' => 'Menu Management',
            'menu.category.add' => 'Add Category',
            'menu.category.none' => 'No categories yet.',
            'menu.category.order' => 'Order',
            'menu.category.copy_to_branches' => 'Copy to Branches',
            'menu.category.delete_confirm' => 'Delete category?',
            'menu.item.add' => 'Add Item',
            'menu.item.none' => 'No items.',
            'menu.item.delete_confirm' => 'Delete item?',
            'menu.none' => 'No menus found.',
            'menu.categories.none' => 'No categories.',

            // Sizes
            'sizes.index.title' => 'Sizes',
            'sizes.add' => 'Add Size',
            'sizes.none' => 'No sizes yet.',
            'sizes.delete_confirm' => 'Delete size?',

            // Bases
            'bases.index.title' => 'Bases',
            'bases.add' => 'Add Base',
            'bases.none' => 'No bases yet.',
            'bases.delete_confirm' => 'Delete base?',
        ],
        'fi' => [
            // Navigation
            'nav.role.admin' => 'Admin',
            'nav.role.branch_admin' => 'Sivukonttorin järjestelmänvalvoja',
            'nav.role.restaurant_admin' => 'Ravintolan ylläpitäjä',
            'nav.dashboard' => 'Kojelauta',
            'nav.orders' => 'Tilaukset',
            'nav.menu' => 'Valikko',
            'nav.menu_sizes' => 'Valikon koot',
            'nav.menu_bases' => 'Valikkopohjat',
            'nav.staff' => 'Henkilökunta',
            'nav.reports' => 'Raportit',
            'nav.notifications' => 'Ilmoitukset',
            'nav.settings' => 'Asetukset',
            'nav.logout' => 'Kirjaudu ulos',

            // Branch selector
            'branches.all' => 'Kaikki haarat',
            'branches.clear' => 'Selkeä',
            'branch' => 'Haara',

            // Common
            'common.name' => 'Nimi',
            'common.price' => 'Hinta',
            'common.available' => 'Saatavilla',
            'common.active' => 'Aktiivinen',
            'common.yes' => 'Kyllä',
            'common.no' => 'Ei',
            'common.edit' => 'Muokkaa',
            'common.delete' => 'Poista',
            'common.add' => 'Lisätä',
            'common.create' => 'Luoda',
            'common.update' => 'Päivittää',
            'common.save' => 'Tallentaa',
            'common.cancel' => 'Peruuttaa',
            'common.action' => 'Toiminta',

            // Field labels
            'field.restaurant' => 'Ravintola',
            'field.branch' => 'Haara',
            'field.name_en' => 'Nimi (englanniksi)',
            'field.name_fi' => 'Nimi (suomi)',
            'field.description_en' => 'Kuvaus (englanniksi)',
            'field.description_fi' => 'Kuvaus (suomi)',
            'field.default_price' => 'Oletushinta',
            'field.display_order' => 'Näytä järjestys',
            'field.item_price_optional' => 'Tuotteen hinta (valinnainen)',
            'field.available' => 'Saatavilla',
            'field.image_url' => 'Kuvan URL-osoite',
            'field.sizes_select' => 'Koot (valitse yksi tai useampi)',
            'field.bases_select' => 'Pohjat (valitse yksi tai useampi)',
            'select.restaurant' => 'Valitse ravintola',
            'placeholder.price_override_optional' => 'Hinta (ohitus valinnainen)',
            'placeholder.item_price_hint' => 'Jätä tyhjäksi, jos haluat laskea koon ja perusarvon automaattisesti yhteen',

            // Form titles
            'sizes.edit.title' => 'Muokkaa kokoa',
            'bases.create.title' => 'Lisää pohja',
            'bases.edit.title' => 'Muokkaa pohjaa',
            'categories.create.title' => 'Luo luokka',
            'categories.edit.title' => 'Muokkaa luokkaa',
            'items.create.title' => 'Lisää kohde',
            'items.edit.title' => 'Muokkaa kohdetta',

            // Menu index
            'menu.index.title' => 'Valikon hallinta',
            'menu.category.add' => 'Lisää luokka',
            'menu.category.none' => 'Ei vielä luokkia.',
            'menu.category.order' => 'Tilata',
            'menu.category.copy_to_branches' => 'Kopioi sivukonttoriin',
            'menu.category.delete_confirm' => 'Poistetaanko luokka?',
            'menu.item.add' => 'Lisää kohde',
            'menu.item.none' => 'Ei kohteita.',
            'menu.item.delete_confirm' => 'Poistetaanko kohde?',
            'menu.none' => 'Valikoita ei löytynyt.',
            'menu.categories.none' => 'Ei luokkia.',

            // Sizes
            'sizes.index.title' => 'koot',
            'sizes.add' => 'Lisää koko',
            'sizes.none' => 'Ei vielä kokoja.',
            'sizes.delete_confirm' => 'Poistetaanko koko?',

            // Bases
            'bases.index.title' => 'Pohjat',
            'bases.add' => 'Lisää pohja',
            'bases.none' => 'Ei vielä perusteita.',
            'bases.delete_confirm' => 'Poistetaanko kanta?',
        ],
    ];

    public static function t(string $key, ?string $lang = null): string
    {
        $lang = $lang ?: (session('ui_lang') ?: 'en');
        $lang = in_array($lang, ['en','fi']) ? $lang : 'en';
        if (isset(self::$map[$lang][$key])) {
            return self::$map[$lang][$key];
        }
        // fallback to en, else key
        return self::$map['en'][$key] ?? $key;
    }
}
