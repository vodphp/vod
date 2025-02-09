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
}
