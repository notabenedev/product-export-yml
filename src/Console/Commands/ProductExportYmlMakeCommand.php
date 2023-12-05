<?php

namespace Notabenedev\ProductExportYml\Console\Commands;

use PortedCheese\BaseSettings\Console\Commands\BaseConfigModelCommand;

class ProductExportYmlMakeCommand extends BaseConfigModelCommand
{

    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'make:product-export-yml
                    {--all : Run all}
                    {--controllers : Export controllers}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Make product-export-yml settings';
    protected $vendorName = 'Notabenedev';
    protected $packageName = "ProductExportYml";


    /**
     * Make Controllers
     */
    protected $controllers = [
        "Site" => ["ProductExportYmlController"],
    ];


    /**
     * Create a new command instance.
     *
     * @return void
     */
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $all = $this->option("all");

        if ($this->option("controllers") || $all) {
             $this->exportControllers("Site");
        }

    }

}
