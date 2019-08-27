Бандл, который реализует адаптер selectel для flysystem.
========================================================

Бандл реализует адаптер selectel для [flysystem](https://flysystem.thephpleague.com/docs/). Предназначен в первую 
очередь для тех сервисов, которые хотят использовать абстракцию flysystem для доступа к облачному хранилищу selectel.



Установка.
----------

Бандл устанавливается с помощью `composer` и следует стандартной структуре, поэтому на `symfony >=4.2` устанавливается 
автоматически.

1. Добавить репозиторий в `composer.json` проекта:

    ```json
    "repositories": [
        {
            "type": "git",
            "url": "https://git.crtweb.ru/youtool/selectel-bundle"
        }
    ]
    ```

2. Добавить пакет бандла в проект:

    ```bash
    $ composer require youtool/selectel-bundle
    ```

3. Настроить доступ к облачному хранилищу selectel:

    ```yaml
    # app/config/packages/youtool_selectel.yaml
    youtool_selectel:
        account_id: 123123
        client_id: 123123_prod
        client_password: prod_password
        container: prod_container
    ```

4. Добавить адаптер selectel в flysytem:

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
                    service: youtool_selectel.adapter.adapter
        filesystems:
            default_filesystem:
                adapter: selectel.flysystem_adapter
                alias: League\Flysystem\Filesystem
    ```

Настройка.
----------

Доступные опции бандла:

* `account_id` - идентификатор аккаунта на selectel,

* `client_id` - идентификатор пользователя selectel, от имени которого будет осуществляться доступ к хранилищу,

* `client_password` - пароль пользователя,

* `container` - контейнер, в котором будут храниться файлы.



Использование в локальном окружении.
------------------------------------

Для локальной разработки или для запуска тестов **ни в коем случае нельзя** использовать боевой контейнер. 
Следует либо ввести координаты тестового контейнера:

```yaml
# app/config/packages/dev/youtool_selectel.yaml для локальной разработки
# app/config/packages/test/youtool_selectel.yaml для тестов
youtool_selectel:
    account_id: 123123
    client_id: 123123_test
    client_password: test_password
    container: test_container
```

Либо использовать flysystem адаптер для локальной файловой системы:

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
