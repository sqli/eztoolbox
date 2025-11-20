SQLI eZ Toolbox Bundle
========================================

[SQLI](http://www.sqli.com) eZToolbox is a bundle used in SQLI projects gathering some bundles like "SQLI Entities Manager", "SQLI Command Toolbox", some helpers and some Twig operators
Compatible with eZPlatform/Ibexa >= 2.x.

eZToolbox follow the same major release number as Ibexa (eZPlatform for older versions), so please adapt the composer require command to match with your version of Ibexa.

| Ibexa version  | eZToolbox's version |
|----------------|---------------------|
| eZPlatform 2.x | sqli/eztoolbox=^2.0 |
| Ibexa 3.x      | sqli/eztoolbox=^3.0 |
| Ibexa 4.x      | sqli/eztoolbox=^4.0 |
| Ibexa 5.x      | sqli/eztoolbox=^5.0 |

Installation
------------

### Install with composer
```
composer require sqli/eztoolbox=^5.0
```

### Register the bundle

Activate the bundle in `config/bundles.php` AFTER all eZSystem/Ibexa bundles

```php
// config/bundles.php

return [
    // ...
    SQLI\EzToolboxBundle\SQLIEzToolboxBundle::class => ['all' => true],
];
```

### Add routes

In `config/routes/sqli_eztoolbox.yaml` :

```yml
# SQLI Admin routes
_sqli_eztoolbox:
    resource: "@SQLIEzToolboxBundle/Resources/config/routing.yaml"
    prefix: /
```

### Clear cache

```bash
php bin/console cache:clear
```

### Parameters

##### Full example

In `config/packages/sqli_eztoolbox.yaml` add the localisations and namespaces of the entities :

```yaml
sqli_ez_toolbox:
    entities:
        - { directory: 'Entity/Doctrine', namespace: 'App\Entity'}
    admin_logger:
        enabled: true
    storage_filename_cleaner:
        enabled: true
```

### How to use

*(Optional) Change label tabname*

You can change label of the default tab using this translation key for domain `sqli_admin` : **sqli_admin__menu_entities_tab__default**

[Entities Manager](doc/README_entities_manager.md)

[Toolbox](doc/README_toolbox.md)

### Other

[Changelogs](doc/CHANGELOGS.md)

[Upgrade](doc/UPGRADE.md)
