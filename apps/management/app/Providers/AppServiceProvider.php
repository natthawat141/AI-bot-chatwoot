<?php

namespace App\Providers;

use App\Models\ServicePackage;
use App\Policies\PackagePolicy;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Vite;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Single resources serialize flat (no "data" wrapper) so Inertia props map
        // cleanly to the React models. Paginated collections still expose data/meta.
        JsonResource::withoutWrapping();

        // ServicePackage does not follow the default {Model}Policy naming, so map it explicitly.
        Gate::policy(ServicePackage::class, PackagePolicy::class);

        Vite::prefetch(concurrency: 3);
    }
}
