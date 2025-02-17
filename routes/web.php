<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return ['Laravel' => app()->version()];
});

Route::get('/test/pass', function () {
    $ticket = \Cache::rememberForever('test:ticket', function () {
        return (object)[
            'issue_date' => null,
            'expiry_date' => null,
            'paused_date' => null,
            'remaining_time' => 120, // minutes
            'features' => [],
        ];
    });

    $isCheckOut = $ticket->issue_date;

    if ($isCheckOut) {
        $now = \Carbon\Carbon::now();
        $timeDiff = $ticket->issue_date->diffInMinutes($now);

        dump(compact('now', 'timeDiff'));
//        $ticket->paused_date = $now;
//        $ticket->remaining_time = $ticket->remaining_time - $timeDiff;
    } else {
        $ticket->issue_date = \Carbon\Carbon::now();
        $ticket->paused_date = null;
    }

    \Cache::forever('test:ticket', $ticket);

    dd($ticket);

});


