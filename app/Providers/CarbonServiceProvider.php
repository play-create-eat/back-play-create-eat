<?php

namespace App\Providers;

use Carbon\Carbon;
use Carbon\CarbonImmutable;
use Carbon\CarbonInterface;
use Illuminate\Support\ServiceProvider;

class CarbonServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        $this->addBusinessWeekendMacro();
    }

    private function addBusinessWeekendMacro(): void
    {
        Carbon::macro('isBusinessWeekend', function () {
            return in_array($this->dayOfWeek, [
                CarbonInterface::FRIDAY,
                CarbonInterface::SATURDAY,
                CarbonInterface::SUNDAY
            ]);
        });

        CarbonImmutable::macro('isBusinessWeekend', function () {
            return in_array($this->dayOfWeek, [
                CarbonInterface::FRIDAY,
                CarbonInterface::SATURDAY,
                CarbonInterface::SUNDAY
            ]);
        });
    }
}
