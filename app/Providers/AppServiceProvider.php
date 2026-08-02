<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Support\ServiceProvider;
use App\Policies\CaseManagerPolicy;
use Illuminate\Support\Facades\Mail;


class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
     public function register(): void {}

    public function boot(): void
    {
       
    }



    protected $policies = [
        // ... policies existentes ...
        User::class => CaseManagerPolicy::class,
    ];
}
