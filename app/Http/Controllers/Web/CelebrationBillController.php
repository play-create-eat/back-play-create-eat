<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Celebration;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;
use Illuminate\Http\Response;

class CelebrationBillController extends Controller
{
    /**
     * Generate and download the celebration bill as PDF
     *
     * @param Celebration $celebration
     * @return Response
     */
    public function printBill(Celebration $celebration)
    {
        $celebration->load([
            'package',
            'invitations',
            'family',
            'theme',
            'cake',
            'menuItems',
            'modifierOptions'
        ]);

        $pdf = PDF::loadView('pdf.celebration-bill', [
            'celebration' => $celebration,
            'packagePrice' => $this->getPackagePrice($celebration),
            'dateFormatted' => Carbon::parse($celebration->celebration_date)->format('d M Y'),
            'isWeekend' => Carbon::parse($celebration->celebration_date)->isBusinessWeekend(),
            'actualChildrenCount' => $celebration->invitations->count(),
            'billingChildrenCount' => max($celebration->invitations->count(), $celebration->package->min_children),
            'minChildrenRequired' => $celebration->package->min_children,
        ]);


        return $pdf->stream('celebration-bill-' . $celebration->id . '.pdf');
    }

    /**
     * Calculate the package price (weekday vs weekend)
     *
     * @param Celebration $celebration
     * @return int
     */
    protected function getPackagePrice(Celebration $celebration): int
    {
        return (Carbon::parse($celebration->celebration_date)->isBusinessWeekend()
            ? $celebration->package->weekend_price
            : $celebration->package->weekday_price) * 100;
    }

    public function printMenu(Celebration $celebration)
    {
        $celebration->load([
            'package',
            'invitations',
            'family',
            'theme',
            'cake',
            'cart.items.menuItem.tags',
            'cart.items.menuItem.type',
            'cart.items.modifiers.modifierOption'
        ]);

        $pdf = PDF::loadView('pdf.celebration-menu', [
            'celebration' => $celebration,
            'dateFormatted' => Carbon::parse($celebration->celebration_date)->format('d M Y'),
            'actualChildrenCount' => $celebration->invitations->count(),
        ]);

        return $pdf->stream('celebration-menu-' . $celebration->id . '.pdf');
    }
}
