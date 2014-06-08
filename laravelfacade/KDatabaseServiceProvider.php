<?php

namespace kingk85;

use Illuminate\Support\ServiceProvider;

class KdatabaseServiceProvider extends ServiceProvider {

    public function register()
    {
        $this->app->bind('kdatabase', function()
        {
            return new Database;
        });
    }
}