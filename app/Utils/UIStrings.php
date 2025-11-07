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
            'nav.branches' => 'Branches',
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

            // Dashboard
            'dashboard.title' => 'Dashboard',
            'dashboard.welcome' => 'Welcome, admin!',
            'dashboard.recent_orders' => 'Recent Orders',
            'dashboard.order_stats' => 'Order Stats',
            'table.id' => 'ID',
            'table.status' => 'Status',
            'table.total' => 'Total',
            'table.created' => 'Created',

            // Orders
            'orders.title' => 'Order Management',
            'orders.none' => 'No orders found.',
            'orders.payment' => 'Payment',
            'orders.type' => 'Type',

            // Staff
            'staff.title' => 'Staff',
            'staff.add' => 'Add Staff',
            'staff.none' => 'No staff yet.',
            'staff.none_branch' => 'No staff found.',
            'staff.name' => 'Name',
            'staff.email' => 'Email',
            'staff.role' => 'Role',
            'staff.active' => 'Active',
            'staff.delete_confirm' => 'Delete staff?',

            // Notifications
            'notifications.title' => 'Notification Management',
            'notifications.title_label' => 'Title',
            'notifications.body_label' => 'Body',
            'notifications.by_token' => 'By Token',
            'notifications.by_topics' => 'By Topics',
            'notifications.device_token' => 'Device Token',
            'notifications.device_token_placeholder' => 'FCM device token',
            'notifications.token_hint' => 'When a token is provided, topics will be ignored.',
            'notifications.restaurant_id' => 'Restaurant ID',
            'notifications.region' => 'Region',
            'notifications.group' => 'Group',
            'notifications.custom_topic' => 'Custom Topic',
            'notifications.topics_hint' => 'Messages will be sent to all provided topics.',
            'notifications.send' => 'Send Notification',

            // Reports
            'reports.title' => 'Reports & Analytics',
            'reports.period' => 'Period',
            'reports.period.daily' => 'Daily',
            'reports.period.weekly' => 'Weekly',
            'reports.period.monthly' => 'Monthly',
            'reports.branch' => 'Branch',
            'reports.all_branches' => 'All branches',
            'reports.refresh' => 'Refresh',
            'reports.export' => 'Export',
            'reports.type' => 'Type',
            'reports.sales' => 'Sales',
            'reports.top_items' => 'Top Items',
            'reports.busy_slots' => 'Busiest Hours',
            'reports.table.period' => 'Period',
            'reports.table.orders' => 'Orders',
            'reports.table.total' => 'Total',

            // Settings
            'settings.title' => 'System Settings',
            'settings.description' => 'Payment, delivery, localization, and GDPR tools go here.',
            'settings.payments' => 'Payments',
            'settings.payments.gateway' => 'Gateway',
            'settings.enabled' => 'Enabled',
            'settings.features' => 'Features',
            'settings.delivery' => 'Delivery',
            'settings.pickup' => 'Pickup',
            'settings.pricing_defaults' => 'Pricing Defaults',
            'settings.tax_rate' => 'Tax Rate (%)',
            'settings.service_charge' => 'Service Charge (%)',
            'settings.delivery_fee' => 'Delivery Fee',
            'settings.localization' => 'Localization',
            'settings.default_locale' => 'Default Locale',
            'settings.locales_csv' => 'Locales (comma separated)',
            'settings.gdpr' => 'GDPR',
            'settings.consent_required' => 'Consent required',
            'settings.retention_days' => 'Retention Days',
            'settings.delete_user_data' => 'Delete User Data',
            'settings.user_id' => 'User ID',
            'settings.user_email_optional' => 'User Email (optional)',
            'settings.export_consent_logs' => 'Export Consent Logs (CSV)',
            'settings.save_settings' => 'Save Settings',
            'settings.no_permission' => 'You do not have permission to view or modify system settings.',

            // Settings: Context
            'settings.context.title' => 'Restaurant Settings',
            'settings.context.restaurant' => 'Restaurant',
            'settings.context.choose_restaurant' => '-- Choose Restaurant --',
            'settings.context.manage_restaurants' => 'Manage Restaurants',
            'settings.context.current_restaurant' => 'Current Restaurant',
            'settings.context.branch_hint' => 'Branch is selected per-page from top right selector.',
            'settings.context.manage_branches' => 'Manage Branches',
            'settings.context.save_restaurant' => 'Save Restaurant',
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
            'nav.branches' => 'Toimipisteet',
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
            'sizes.index.title' => 'Koot',
            'sizes.add' => 'Lisää koko',
            'sizes.none' => 'Ei vielä kokoja.',
            'sizes.delete_confirm' => 'Poistetaanko koko?',

            // Bases
            'bases.index.title' => 'Pohjat',
            'bases.add' => 'Lisää pohja',
            'bases.none' => 'Ei vielä perusteita.',
            'bases.delete_confirm' => 'Poistetaanko kanta?',

            // Dashboard
            'dashboard.title' => 'Kojelauta',
            'dashboard.welcome' => 'Tervetuloa, ylläpitäjä!',
            'dashboard.recent_orders' => 'Viimeaikaiset tilaukset',
            'dashboard.order_stats' => 'Tilaustilastot',
            'table.id' => 'Tunnus',
            'table.status' => 'Tila',
            'table.total' => 'Yhteensä',
            'table.created' => 'Luotu',

            // Orders
            'orders.title' => 'Tilausten hallinta',
            'orders.none' => 'Tilauksia ei löytynyt.',
            'orders.payment' => 'Maksu',
            'orders.type' => 'Tyyppi',

            // Staff
            'staff.title' => 'Henkilökunta',
            'staff.add' => 'Lisää henkilöstöä',
            'staff.none' => 'Ei vielä henkilökuntaa.',
            'staff.none_branch' => 'Henkilökuntaa ei löytynyt.',
            'staff.name' => 'Nimi',
            'staff.email' => 'Sähköposti',
            'staff.role' => 'Rooli',
            'staff.active' => 'Aktiivinen',
            'staff.delete_confirm' => 'Poistetaanko henkilöstö?',

            // Notifications
            'notifications.title' => 'Ilmoitusten hallinta',
            'notifications.title_label' => 'Otsikko',
            'notifications.body_label' => 'Sisältö',
            'notifications.by_token' => 'Tunnuksen mukaan',
            'notifications.by_topics' => 'Aiheiden mukaan',
            'notifications.device_token' => 'Laitteen tunnus',
            'notifications.device_token_placeholder' => 'FCM-laitetunnus',
            'notifications.token_hint' => 'Kun tunnus on annettu, aiheita ei huomioida.',
            'notifications.restaurant_id' => 'Ravintolan tunnus',
            'notifications.region' => 'Alue',
            'notifications.group' => 'Ryhmä',
            'notifications.custom_topic' => 'Mukautettu aihe',
            'notifications.topics_hint' => 'Viestit lähetetään kaikkiin annettuihin aiheisiin.',
            'notifications.send' => 'Lähetä ilmoitus',

            // Reports
            'reports.title' => 'Raportit ja analytiikka',
            'reports.period' => 'Ajanjakso',
            'reports.period.daily' => 'Päivittäin',
            'reports.period.weekly' => 'Viikoittain',
            'reports.period.monthly' => 'Kuukausittain',
            'reports.branch' => 'Haara',
            'reports.all_branches' => 'Kaikki haarat',
            'reports.refresh' => 'Päivitä',
            'reports.export' => 'Vie',
            'reports.type' => 'Tyyppi',
            'reports.sales' => 'Myynti',
            'reports.top_items' => 'Suosituimmat tuotteet',
            'reports.busy_slots' => 'Ruuhkaisimmat tunnit',
            'reports.table.period' => 'Jakso',
            'reports.table.orders' => 'Tilaukset',
            'reports.table.total' => 'Yhteensä',

            // Settings
            'settings.title' => 'Järjestelmäasetukset',
            'settings.description' => 'Maksu-, toimitus-, lokalisointi- ja GDPR-työkalut täällä.',
            'settings.payments' => 'Maksut',
            'settings.payments.gateway' => 'Maksuväylä',
            'settings.enabled' => 'Käytössä',
            'settings.features' => 'Ominaisuudet',
            'settings.delivery' => 'Kotiinkuljetus',
            'settings.pickup' => 'Nouto',
            'settings.pricing_defaults' => 'Hinnoittelun oletukset',
            'settings.tax_rate' => 'Veroprosentti (%)',
            'settings.service_charge' => 'Palvelumaksu (%)',
            'settings.delivery_fee' => 'Toimitusmaksu',
            'settings.localization' => 'Lokalisointi',
            'settings.default_locale' => 'Oletuslokalisointi',
            'settings.locales_csv' => 'Lokalisoinnit (pilkuilla erotettu)',
            'settings.gdpr' => 'GDPR',
            'settings.consent_required' => 'Suostumus vaaditaan',
            'settings.retention_days' => 'Säilytyspäivät',
            'settings.delete_user_data' => 'Poista käyttäjän tiedot',
            'settings.user_id' => 'Käyttäjätunnus',
            'settings.user_email_optional' => 'Käyttäjän sähköposti (valinnainen)',
            'settings.export_consent_logs' => 'Vie suostumuslokit (CSV)',
            'settings.save_settings' => 'Tallenna asetukset',
            'settings.no_permission' => 'Sinulla ei ole oikeuksia tarkastella tai muokata järjestelmäasetuksia.',

            // Settings: Context
            'settings.context.title' => 'Ravintola-asetukset',
            'settings.context.restaurant' => 'Ravintola',
            'settings.context.choose_restaurant' => '-- Valitse ravintola --',
            'settings.context.manage_restaurants' => 'Hallinnoi ravintoloita',
            'settings.context.current_restaurant' => 'Nykyinen ravintola',
            'settings.context.branch_hint' => 'Toimipiste valitaan sivukohtaisesti oikean yläkulman valitsimesta.',
            'settings.context.manage_branches' => 'Hallinnoi toimipisteitä',
            'settings.context.save_restaurant' => 'Tallenna ravintola',
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
