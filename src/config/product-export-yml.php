<?php
return [
    "siteRoutes" => true,
    "productExportYmlSiteUrlName" => "market",
    // short | description field to offer
    "productExportDescriptionField" => "description",
    // php artisan cache:clear after change cacheLifetime
    "cacheLifetime" => 86400,
    "cacheKey" => "export-yml",
];