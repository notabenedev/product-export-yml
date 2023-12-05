<?php

namespace Notabenedev\ProductExportYml\Http\Controllers\Site;

use App\Category;
use App\Http\Controllers\Controller;
use App\Product;
use Illuminate\Support\Facades\Cache;


class ProductExportYmlController extends Controller
{
    /**
     * Get yml
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $key =  config('product-export-yml.cacheKey', "export-yml");
        $yml = Cache::remember( $key, config('product-export-yml.cacheLifetime', 0), function () {
            $field = config("product-export-yml.productExportDescriptionField","description");
            $file = new \SimpleXMLElement("<?xml version='1.0' encoding='UTF-8' ?><yml_catalog></yml_catalog>");
            $file->addAttribute('date', now());
            $shop = $file->addChild("shop");
            $shop->addChild("name",  env("APP_NAME") );
            $shop->addChild("company",  env("APP_NAME") );
            $shop->addChild("url",  env("APP_URL") );
            $currencies = $shop->addChild("currencies",  env("APP_URL") );
            $currency = $currencies->addChild("currency");
            $currency->addAttribute("id","RUR");
            $currency->addAttribute("rate", "1");
            $categoriesYml = $shop->addChild("categories");

            $categories = Category::query()->select("id","parent_id", "title");
            $categoriesFilter = config("product-export-yml.categoriesFilterField",null);
            if (! empty($categoriesFilter))
                $categories->whereNotNull($categoriesFilter);
            $categories->chunk(100, function ($categories) use ($categoriesYml) {
                foreach ($categories as $category) {
                    $categoryYml = $categoriesYml->addChild("category", $category->title);
                    $categoryYml->addAttribute("id", $category->id);
                    if ($category->parent_id)
                        $categoryYml->addAttribute("parentId", $category->parent_id);
                }
            });

            $offersYml = $shop->addChild("offers");
            $products = Product::query()->select("id", "title", $field, "slug", "category_id", "short");
            $productsFilter = config("product-export-yml.productsFilterField",null);
            if (! empty($productsFilter))
                $products->whereNotNull($productsFilter);
            $products->with("variations")
                ->with("images")
                ->chunk(100, function ($products) use ($offersYml, $field) {
                foreach ($products as $product) {
                    // first image
                    $imageSrc = false;
                    foreach ($product->images as $img)
                    {
                        $imageSrc = route('imagecache', ['template' => 'original', 'filename' => $img->file_name]);
                        break;
                    }
                    // description
                    $description = (config("product-export-yml.productDescriptionField", "description") == "description") ?
                        (config("product-export-yml.productDescriptionStripTags", true) ?
                            strip_tags($product->description) :
                            (! empty($product->description) ? '<![CDATA[ '.$product->description.' ]]>' : '' )
                        ):
                        $product->short;
                    // shortDescription if  productDescriptionField ! == short
                    $shortDescription = config("product-export-yml.productDescriptionField", "description") !== "short" ?
                        $product->short : null;
                    // generate xml
                    foreach ($product->variations as $variation){
                        if (! empty($variation->description)){
                            if (empty($shortDescription))
                                $shortDescription .= "$variation->description";
                                else
                                $shortDescription .= " ($variation->description)";
                        }

                        $offerYml = $offersYml->addChild("offer");
                        $offerYml->addAttribute("id", $variation->id);
                        $offerYml->addChild("name", htmlspecialchars($product->title));
                        $offerYml->addChild("url", route("catalog.products.show", ["product" => $product->slug]));
                        $offerYml->addChild("price", $variation->price);
                        if(!empty($variation->sale_price) && $variation->sale_price > 0  )
                            $offerYml->addChild("oldprice", $variation->sale_price);
                        $offerYml->addChild("categoryId", $product->category_id);
                        $offerYml->addChild("description", $description);
                        if (! empty($shortDescription))
                            $offerYml->addChild("shortDescription", $shortDescription);
                        if(! empty($variation->disabled_at)){
                            $offerYml->addChild("available","false");
                            $offerYml->addChild("store","false");
                        } else{
                            $offerYml->addChild("available","true");
                            $offerYml->addChild("store","true");
                        }
                        if ($imageSrc)
                            $offerYml->addChild("picture", "$imageSrc");
                    }
                }
            });
            return $file->asXML();
         });
        return response($yml, 200)->header('Content-Type', 'text/xml') ;
    }
}
