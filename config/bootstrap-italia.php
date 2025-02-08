<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Title
    |--------------------------------------------------------------------------
    |
    | The default title of your pages, this goes into the title tag
    | of your page. You can override it per page with the title section.
    | You can optionally also specify a title prefix and/or postfix.
    |
    |--------------------------------------------------------------------------
    */

    'title' => 'APPosto',

    'title_prefix' => '',

    'title_postfix' => '',

    /*
    |--------------------------------------------------------------------------
    | Logo / brand-text / tagline / owner
    |--------------------------------------------------------------------------
    |
    | Logo and brand-text are displayed at the upper left corner of your pages.
    | You can specify an icon (with its name) or an image (with its url)
    | The logo has also a tagline, used as subtitle. The owner is the
    | institution owning the application
    |
    |--------------------------------------------------------------------------
    */


    'logo' => [
      //  'type' => 'icon',
        //'icon' => 'pa',
    ],

    /*
    'logo' => [
        'type' => 'url',
        'url' => 'img/logo.png',
    ],
    */

    'brand-text' => 'APPosto',

    'brand-text-small' => 'APPosto',

    'tagline' => '',

    'owner' => [
        'description' => 'Dipartimento per la trasformazione digitale',
        'link' => '#',
    ],

    /*
    |--------------------------------------------------------------------------
    | Appearance of the pages
    |--------------------------------------------------------------------------
    |
    | - slim-header-light: set to true for a white slim-header
    | - small-header: set to true for a smaller header height
    |
    |--------------------------------------------------------------------------
    */

    'slim-header-light' => false,

    'small-header' => false,

    'sticky-header' => false,

    /*
    |--------------------------------------------------------------------------
    | Auth section
    |--------------------------------------------------------------------------
    |
    | Set to false or null if you do not require authentication.
    |
    |--------------------------------------------------------------------------
    */

    'auth' => [

        'login' => [
            'type' => 'route',
            'route' => 'login',
        ],

        'logout' => [
            'type' => 'route',
            'route' => 'logout',
            'method' => 'post',
        ],

    ],


    /*
    |--------------------------------------------------------------------------
    | Additional links
    |--------------------------------------------------------------------------
    |
    | Set to false or null to hide element, set type to url or route
    | according to your needs.
    |
    |--------------------------------------------------------------------------
    */

    'routes' => [

        'home' => [
            'type' => 'url',
            'url' => '/',
        ],

        /* You can also specify a route instead
        'home' => [
            'type' => 'route',
            'route' => home',
        ],
         

        'search' => [
            'type' => 'url',
            'url' => '#',
        ],

        'newsletter' => [
            'type' => 'url',
            'url' => '#',
        ],*/
    ],

    /*
    |--------------------------------------------------------------------------
    | Extra components
    |--------------------------------------------------------------------------
    |
    | Set to false or null if you want to hide the elements.
    |
    |--------------------------------------------------------------------------
    */

    'socials' => [
        /*[
            'icon' => 'designers-italia',
            'text' => 'Designer Italia',
            'url' => '#',
        ],
        [
            'icon' => 'twitter',
            'text' => 'Twitter',
            'url' => '#',
        ],
        [
            'icon' => 'medium',
            'text' => 'Medium',
            'url' => '#',
        ],
        [
            'icon' => 'behance',
            'text' => 'Behance',
            'url' => '#',
        ],*/
    ],


    /*
    |--------------------------------------------------------------------------
    | Menu Items
    |--------------------------------------------------------------------------
    |
    | Specify your menu items to display for:
    | - slim-header
    | - header
    | - footer-menu
    | - footer-bar
    |
    | Each menu item should have a text and a URL. A string instead of an array
    | represents a header. The 'can' is a filter on Laravel's built in Gate
    | functionality.
    | 
    | Address and contacts-links can be set to false or null.
    |
    | See details in the readme for configuring dropdowns and megamenus.
    |
    */

    'menu' => [
        'slim-header' => [
            [
                'url' => '/',
                'text' => 'Pagina iniziale',
            ],
            [
                'url' => 'contatti',
                'text' => 'Contatti',
                'target' => '_blank',
            ],
        ],
        'header' => [
           
            [
                'url' => '/dashboard',
                'text' => 'Menu generale',
            ] ,
            [
                'text' => 'Link veloci',
                'megamenu' => [
                    [
                        'Prenotazioni',
                        [
                            'url' => '/booking',
                            'text' => 'Prenota',
                          //  'target' => '_blank',
                           // 'can' => 'set',
                        ],
                        '-',
                        [
                            'url' => '/booking/history',
                            'text' => 'Storico',
                        ],
                        '-',
                        [
                            'url' => '/booking/current',
                            'text' => 'Attive',
                        ],
                    ],
                    [
                        'Varie',
                        [
                            'url' => '/contacts',
                            'text' => 'Cerca collega',
                        ],
                        '-',
                        [
                            'url' => '/profile',
                            'text' => 'Modifica Info',
                        ],
                        '-',
                        [
                            'url' => '/booking/check-desk',
                            'text' => 'Verifica Disponibilità',
                        ],
                    ],
                ]
            ],

        ],
        'footer' => [
            
        ],
        'footer-bar' => [
            [
                'url' => env('OPEN_SPACE_DTD', 'https://governoit.sharepoint.com/sites/DTD-Documentale'),
                'title' => 'Open Space del DTD',
                'text' => 'Open Space del DTD',
                'target' => '_blank',
            ],
            [
                'url' => env('SEDI_DIPARTIMENTO', 'https://governoit.sharepoint.com/sites/DTD-Documentale/SitePages/Sedi-DTD.aspx'),
                'title' => 'Sedi del DTD',
                'text' => 'Sedi del DTD',
                'target' => '_blank',
            ],
            [
                'url' => env('POLICY_SPAZI', 'https://governoit.sharepoint.com/:w:/r/sites/DTD-Documentale/_layouts/15/Doc.aspx?sourcedoc=%7B79E13108-D46D-4CC5-A66D-65BA66F4A78D%7D&file=Policy_Spazi_Via_Alessandria.docx&action=default&mobileredirect=true') ,
                'text' => 'Policy degli spazi',
                'target' => '_blank',
            ]
        ]
    ],

   'address' => false,//<strong>Comune di Lorem Ipsum</strong><br> Via Roma 0 - 00000 Lorem Ipsum Codice fiscale / P. IVA: 000000000',

    'contacts-links' => false,

    /*
    |--------------------------------------------------------------------------
    | Menu Filters
    |--------------------------------------------------------------------------
    |
    | Choose what filters you want to include for rendering the menu.
    | You can add your own filters to this array after you've created them.
    | You can comment out the GateFilter if you don't want to use Laravel's
    | built in Gate functionality.
    |
    |--------------------------------------------------------------------------
    */

    'filters' => [
        italia\DesignLaravelTheme\Menu\Filters\HrefFilter::class,
        italia\DesignLaravelTheme\Menu\Filters\ActiveFilter::class,
        italia\DesignLaravelTheme\Menu\Filters\GateFilter::class,
    ],

];