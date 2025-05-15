<?php

namespace Vod\Vod;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Vod\Vod\Commands\VodTransform;

class VodServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('vod')
            ->hasCommands([
                VodTransform::class,
            ])
            ->hasConfigFile();
    }

    public function packageRegistered()
    {

        $this->app->beforeResolving(Vod::class, function ($class, $parameters, $app) {
            if ($app->has($class)) {
                return;
            }
            $app->bind($class, fn ($container) => $class::fromRequest($container['request']));
        });
    }
}
