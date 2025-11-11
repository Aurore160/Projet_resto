<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Repositories\Contracts\UserRepositoryInterface;
use App\Repositories\UserRepository;
use App\Repositories\Contracts\OrderRepositoryInterface;
use App\Repositories\OrderRepository;
use App\Repositories\Contracts\CartRepositoryInterface;
use App\Repositories\CartRepository;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     * 
     * Ici, on dit à Laravel : "Quand quelqu'un demande UserRepositoryInterface,
     * donne-lui UserRepository"
     * 
     * C'est comme un annuaire téléphonique :
     * - Interface = le nom dans l'annuaire
     * - Repository = le numéro de téléphone réel
     */
    public function register(): void
    {
        // Binding : Interface → Implémentation
        $this->app->bind(UserRepositoryInterface::class, UserRepository::class);
        $this->app->bind(OrderRepositoryInterface::class, OrderRepository::class);
        $this->app->bind(CartRepositoryInterface::class, CartRepository::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
