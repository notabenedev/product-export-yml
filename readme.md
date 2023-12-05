## Description
- экспорт категорий, товаров и (если есть) цен в YML файл импорта
- кэширование в течение суток
- задать параметры экспорта мождно в конфиге

## Config

php artisan vendor:publish --provider="Notabenedev\ProductExportYml\ProductExportYmlServiceProvider" --tag=config 

## Install
     -   php artisan make:product-export-yml
                            {--all : Run all}
                            {--controllers: export controllers}
     -   fill config if you need
