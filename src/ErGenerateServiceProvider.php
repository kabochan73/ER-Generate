<?php

namespace Kabochan73\ErGenerate;

use Illuminate\Support\ServiceProvider;
use Kabochan73\ErGenerate\Commands\ErGenerateCommand;

class ErGenerateServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                ErGenerateCommand::class,
            ]);
        }
    }
}
