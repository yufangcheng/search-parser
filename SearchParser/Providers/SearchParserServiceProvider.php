<?php

namespace SearchParser\Providers;

use Illuminate\Support\ServiceProvider;

class SearchParserServiceProvider extends ServiceProvider
{
    public function boot()
    {
        $this->setPublishes();
    }

    public function register()
    {

    }

    protected function setPublishes()
    {
        $configPath = __DIR__ . '/../Config/';
        $this->publishes([
            $configPath . 'parser.php' => config_path('search_parser.php')
        ]);
        $this->mergeConfigFrom($configPath . 'parser.php', 'search_parser');
    }
}
