<?php

namespace App\Providers;


use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     *
     * @return void
     */
    public function register(){
        \Illuminate\Database\Query\Builder::macro("clearOrdersBy", function () {
            $this->{$this->unions ? 'unionOrders' : 'orders'} = null;
            return $this;
        });
    }

    /**
     * Bootstrap any application services.
     *
     * @return void
     */
    public function boot()
    {
        //
    }
}
