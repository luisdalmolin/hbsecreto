<?php

use App\Draws\ClassicDrawAlgorithm;
use App\Draws\DrawAlgorithm;
use App\Models\Conversation;
use App\Models\Edition;
use App\Models\EditionParticipant;
use App\Models\Group;
use App\Models\GroupMember;
use App\Models\Wish;
use Illuminate\Database\Eloquent\Attributes\UsePolicy;

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

arch('core tenant models declare their policies explicitly')
    ->expect([
        Group::class,
        GroupMember::class,
        Edition::class,
        EditionParticipant::class,
        Conversation::class,
        Wish::class,
    ])
    ->toHaveAttribute(UsePolicy::class);

arch('domain orchestration actions are final')
    ->expect('App\\Actions\\Groups')
    ->and('App\\Actions\\Editions')
    ->and('App\\Actions\\Wishes')
    ->and('App\\Actions\\Conversations')
    ->classes()
    ->toBeFinal();

arch('draw algorithm contract stays framework independent')
    ->expect(DrawAlgorithm::class)
    ->toBeInterface()
    ->and(ClassicDrawAlgorithm::class)
    ->not->toUse('Illuminate');
