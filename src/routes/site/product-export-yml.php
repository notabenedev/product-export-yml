<?php

use Illuminate\Support\Facades\Route;

Route::group([
    "namespace" => "App\Http\Controllers\Vendor\ProductExportYml\Site",
    "middleware" => ["web"],
    "as" => "catalog.yml",
    "prefix" => config("product-export-yml.siteUrlName"),
], function () {
    Route::get("", "ProductExportYmlController@index")->name("index");
});