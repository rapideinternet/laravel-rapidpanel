# laravel-rapidpanel

# Installation

* composer require rapide/rapidpanel

* php artisan vendor:publish --tag=config

* Add to providers in config/app.php

Rapide\RapidPanel\RapidPanelServiceProvider::class,

* Add to aliases in config/app.php

'RapidPanel' => Rapide\RapidPanel\RapidPanelServiceProvider::class,

# Usage

$rpClient = new RapidPanelClient(['host' => 'rapidpanelserver.tld']);

$rpCreateResponse = $rpClient->fetch(
    "admin",
    $rpClient->hashPassword("somepass"),
    [
        'object' => 'domain',
        'action' => 'show'
    ]
);
