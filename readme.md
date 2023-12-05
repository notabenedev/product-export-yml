## Description
- экспорт категорий, товаров и (если есть) цен в YML файл импорта

## Config

php artisan vendor:publish --provider="Notabenedev\ProductExportYml\ProductExportYmlServiceProvider" --tag=config 

## Install
     -   php artisan make:product-import
                            {--all : Run all}
                            {--controllers: export controllers}
