<?php
return [
    "siteRoutes" => true,
    "siteUrlName" => "market",
    // short | description field to offer description
    "productDescriptionField" => "description",
    // string | null field to filter data
    "categoriesFilterField" => "published_at",
    "productsFilterField" => "published_at",
    // php artisan cache:clear after change cacheLifetime
    "cacheLifetime" => 0,
    "cacheKey" => "export-yml",
];