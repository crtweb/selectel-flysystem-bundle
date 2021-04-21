Bundle that implements Selectel adapter for flysystem
=====================================================

Bundle implements Selectel adapter for [flysystem](https://flysystem.thephpleague.com/docs/). Designed primarily for 
those services that want to use the flysystem abstraction to access Selectel cloud storage.


Installation
------------

The bundle is installed with `composer` and follows the standard structure, so it is installed automatically on `symfony >= 4.2`

1. Add the repository to project `composer.json`:

    ```json
    "repositories": [
        {
            "type": "git",
            "url": "https://github.com/crtweb/selectel-flysystem-bundle"
        }
    ]
    ```

2. Add the bundle package to the project:

    ```bash
    $ composer require creative/selectel-flysystem-bundle
    ```

3. Add the bundle package to the project:

    ```yaml
    # app/config/packages/creative_selectel.yaml
    creative_selectel:
        account_id: 123123
        client_id: 123123_prod
        client_password: prod_password
        container: prod_container
    ```

4. Add selectel adapter to flysystem:

    ```yaml
    # app/config/packages/oneup_flysystem.yaml
    services:
        League\Flysystem\FilesystemInterface:
            alias: League\Flysystem\Filesystem

    oneup_flysystem:
        adapters:
            default_adapter:
                local:
                    directory: '%kernel.cache_dir%/flysystem'
            selectel.flysystem_adapter:
                custom:
                    service: creative_selectel.adapter.adapter
        filesystems:
            default_filesystem:
                adapter: selectel.flysystem_adapter
                alias: League\Flysystem\Filesystem
    ```

Configuration
-------------

Available bundle options:

* `account_id` - Selectel account identifier

* `client_id` - Selectel user ID, on whose behalf the storage will be accessed

* `client_password` - user password

* `container` - the container in which the files will be stored



Use in a local environment
--------------------------

**Never use the production container** for local development or for running tests.
You should either enter the coordinates of the test container:

```yaml
# app/config/packages/dev/creative_selectel.yaml для локальной разработки
# app/config/packages/test/creative_selectel.yaml для тестов
creative_selectel:
    account_id: 123123
    client_id: 123123_test
    client_password: test_password
    container: test_container
```

Or use the flysystem adapter for the local filesystem:
```yaml
# app/config/packages/dev/oneup_flysystem.yaml для локальной разработки
# app/config/packages/test/oneup_flysystem.yaml для тестов
services:
    League\Flysystem\FilesystemInterface:
        alias: League\Flysystem\Filesystem

oneup_flysystem:
    adapters:
        default_adapter:
            local:
                directory: '%kernel.cache_dir%/flysystem'
    filesystems:
        default_filesystem:
            adapter: default_adapter
            alias: League\Flysystem\Filesystem
```
