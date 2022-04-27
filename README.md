# laravel-tnt

# Table of Contents
- [Installing and configuring](#installing-and-configuring)
    - [Install](#install)
    - [Configure](#configure)
    - [Load Service Provider](#service-provider)
    - [Publishing the configuration file](#config-publish)
- [Usage](#usage)

# <a id="installing-and-configuring"></a> Installing and configuring
## <a id="install"></a> Installing the package
You need to use Composer to install this package into your Laravel project:
```
composer require alagaccia/laravel-tnt
```

## <a id="service-provider"></a> Load Service Provider
From Laravel 5.5 and newer the package wil register itself using Laravel's [Auto Discovery](https://laravel.com/docs/5.5/packages#package-discovery).

Laravel 5.4 and older needs to include `TntServiceProvider` in your `config/app.php`:
```php
'providers' => [
    /*
     * Package Service Providers...
     */
    AndreaLagaccia\TntServiceProvider::class,
]
```

## <a name="config-publish"></a> Publishing the configuration file
Run the following Artisan command in your terminal to copy the environment variables:
```
php artisan vendor:publish --provider="AndreaLagaccia\Tnt\TntServiceProvider"
```
Choose the preference and press ENTER
```
Provider: AndreaLagaccia\Tnt\TntServiceProvider
```
Now you have a `config/tnt.php` config file, where you can set the API_KEY and your favorite LIST_ID.
You can also write your Sendinblue variables in the .env file as follow:
```
TNT_CUSTOMER=
TNT_USER=
TNT_PASSWORD=
TNT_SENDER_ACC_ID=
```

# <a id="usage"></a>  Usage
