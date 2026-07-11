<?php

arch('API controllers are final')
    ->expect('App\\Http\\Controllers\\Api')
    ->classes()
    ->toBeFinal();

arch('API controllers do not use Laravel HTTP resources')
    ->expect('App\\Http\\Controllers\\Api')
    ->not->toUse('Illuminate\\Http\\Resources');

arch('application does not use Form Requests')
    ->expect('App')
    ->not->toExtend('Illuminate\\Foundation\\Http\\FormRequest');

arch('application does not use Laravel JSON resources')
    ->expect('App')
    ->not->toExtend('Illuminate\\Http\\Resources\\Json\\JsonResource')
    ->not->toUse('Illuminate\\Http\\Resources');

arch('API data classes are final')
    ->expect('App\\Data\\Api')
    ->classes()
    ->toBeFinal();

arch('API data classes do not use Laravel HTTP resources')
    ->expect('App\\Data\\Api')
    ->not->toUse('Illuminate\\Http\\Resources');
