<?php

namespace Notabenedev\ProductExportYml\Http\Controllers\Site;

use App\Category;
use App\Http\Controllers\Controller;
use App\Product;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use PortedCheese\CategoryProduct\Facades\ProductActions;


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
            $currencies = $shop->addChild("currencies");
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
                    $imageRoute =  class_exists(\App\ImageFilter::class) ? 'image-filter' : 'imagecache';
                    $imageSrc = null;
                    foreach ($product->images as $img)
                    {
                        $imageSrc = route($imageRoute, ['template' => 'original', 'filename' => $img->file_name]);
                        break;
                    }
                    // description
                    $description = (config("product-export-yml.productDescriptionField", "description") == "description") ?
                        (config("product-export-yml.productDescriptionStripTags", true) ?
                            htmlspecialchars(strip_tags($product->description),ENT_XML1) :
                            (! empty($product->description) ? '<![CDATA[ '.htmlspecialchars($product->description, ENT_XML1).' ]]>' : '' )
                        ):
                        $product->short;
                    // shortDescription if  productDescriptionField ! == short
                    $shortDescription = config("product-export-yml.productDescriptionField", "description") !== "short" ?
                        $product->short : null;

                    // country of origin
                    $productSpecGroups = ProductActions::getProductSpecificationsByGroups($product);
                    $origin = null;
                    $productParams = [];
                    foreach ($productSpecGroups as $group){
                        foreach ($group->specifications as $id => $spec){
                            if ($spec->title == "Производство" || $spec->title == "Производитель")
                            {
                                $origin = implode(", ", $spec->values);
                                break;
                            }
                            else
                            {
                                if (count($spec->values) < 2)
                                    $productParams[$spec->title] = $spec->values[0];
                            }
                        }
                    }

                    // generate xml
                    foreach ($product->variations as $variation){
                        if (($variation->price) == 0) break;
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
                        $offerYml->addChild("currencyId", "RUR");
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
                        if ($origin)
                            $offerYml->addChild("country_of_origin", $origin);
                        foreach ($productParams as $param => $paramValue) {
                            $offerYml->addChild("param", $paramValue)->addAttribute("name",$param);
                        }
                        if ($variation->specifications)
                            foreach ($variation->specifications as $param){
                                $offerYml->addChild("param", $param->value)->addAttribute("name",$param->title);
                            }
                    }
                }
            });
            return $file->asXML();
         });
        return response($yml, 200)->header('Content-Type', 'text/xml') ;
    }
}
