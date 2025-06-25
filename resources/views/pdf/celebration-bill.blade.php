@php use Carbon\Carbon; @endphp
    <!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Celebration Bill #{{ $celebration->id }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            font-size: 12px;
            line-height: 1.5;
            color: #000;
            background-color: #fff;
        }

        .header {
            position: relative;
            text-align: center;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 1px solid #000;
        }

        .company-info {
            position: absolute;
            top: 0;
            right: 0;
            text-align: right;
            font-size: 11px;
            color: #555;
            line-height: 1.3;
            max-width: 200px;
        }

        .company-name {
            font-size: 12px;
            font-weight: bold;
            color: #333;
            margin-bottom: 3px;
        }

        .logo {
            max-width: 200px;
            margin-bottom: 10px;
            margin-top: 10px;
        }

        h1 {
            font-size: 22px;
            color: #000;
            margin: 0 0 5px;
        }

        .bill-info {
            margin-bottom: 20px;
        }

        .bill-info div {
            margin-bottom: 5px;
        }

        .section {
            margin-bottom: 20px;
        }

        .section-title {
            font-size: 16px;
            font-weight: bold;
            border-bottom: 1px solid #000;
            padding-bottom: 5px;
            margin-bottom: 10px;
        }

        .section-subtitle {
            font-size: 14px;
            font-weight: bold;
            margin: 15px 0 10px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        table th, table td {
            padding: 8px;
            text-align: left;
            border-bottom: 1px solid #000;
        }

        table th {
            background-color: #fff;
            font-weight: bold;
        }

        .total-row {
            font-weight: bold;
            background-color: #fff;
            border-top: 2px solid #000;
        }

        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 11px;
            color: #000;
            border-top: 1px solid #000;
            padding-top: 10px;
        }

        .footer-company {
            font-size: 12px;
            color: #333;
            margin-bottom: 5px;
        }

        .footer-message {
            font-size: 11px;
            color: #666;
            font-style: italic;
        }

        .price-column {
            text-align: right;
        }

        .highlighted {
            background-color: #fff;
            font-weight: bold;
        }

        .minimum-package-notice {
            margin: 15px 0;
            padding: 12px;
            background-color: #fff;
            border: 2px solid #000;
            color: #000;
            font-weight: bold;
        }

        .modifier-item {
            padding-left: 20px;
            font-style: italic;
            font-size: 11px;
        }

        .booking-info {
            background-color: #f9f9f9;
            border: 1px solid #ddd;
            padding: 10px;
            margin: 10px 0;
        }
    </style>
</head>
<body>
<div class="header">
    <div class="company-info">
        <div class="company-name">Play Create Eat</div>
        <div>Kids Amusement Arcade L.L.C</div>
    </div>
    @if(file_exists(public_path('images/logo.svg')))
        <img src="{{ public_path('images/logo.svg') }}" alt="Play Create Eat Logo" class="logo">
    @elseif(file_exists(public_path('images/logo.png')))
        <img src="{{ public_path('images/logo.png') }}" alt="Play Create Eat Logo" class="logo">
    @else
        <div style="text-align: center; font-size: 18px; font-weight: bold; margin: 20px 0;">
            Play Create Eat
        </div>
    @endif
    <h1>Celebration Bill</h1>
    <div>Invoice #{{ $celebration->id }}</div>
    <div>Date: {{ now()->format('d M Y') }}</div>
</div>

<div class="bill-info">
    <div><strong>Celebration Date:</strong> {{ $dateFormatted }} ({{ $isWeekend ? 'Weekend' : 'Weekday' }})</div>
    <div><strong>Family:</strong> {{ $celebration->family->name ?? 'N/A' }}</div>
    <div><strong>Parent phone number:</strong> {{ $celebration->user->profile->phone_number }}</div>
    <div><strong>Child:</strong> {{ $celebration->child->full_name ?? 'N/A' }}</div>
    <div><strong>Package:</strong> {{ $celebration->package->name }}</div>
    <div><strong>Theme:</strong> {{ $celebration->theme->name ?? 'N/A' }}</div>
</div>

@if($celebration->bookings->count() > 0)
    <div class="section">
        <div class="section-title">Booking Information</div>
        @foreach($celebration->bookings as $booking)
            <div class="booking-info">
                <div><strong>Celebration
                        Time:</strong> {{ Carbon::parse($booking->start_time)->format('d M Y, h:i A') }}
                    - {{ Carbon::parse($booking->end_time)->format('h:i A') }}</div>
                <div><strong>Children Count:</strong> {{ $booking->children_count }}</div>
                @if($booking->tables->count() > 0)
                    <div><strong>Booked Tables:</strong>
                        @foreach($booking->tables as $table)
                            {{ $table->name }}@if(!$loop->last)
                                ,
                            @endif
                        @endforeach
                        ({{ $booking->tables->count() }} {{ $booking->tables->count() == 1 ? 'table' : 'tables' }})
                    </div>
                @endif
                @if($booking->special_requests)
                    <div><strong>Special Requests:</strong> {{ $booking->special_requests }}</div>
                @endif
            </div>
        @endforeach
    </div>
@endif

<div class="section">
    <div class="section-title">Package Details</div>
    <table>
        <tr>
            <th>Item</th>
            <th>Details</th>
            <th class="price-column">Price (AED)</th>
        </tr>
        <tr>
            <td>{{ $celebration->package->name }}</td>
            <td>{{ $isWeekend ? 'Weekend Rate' : 'Weekday Rate' }}</td>
            <td class="price-column">{{ number_format(($isWeekend ? $celebration->package->weekend_price : $celebration->package->weekday_price), 2) }}</td>
        </tr>
        @if($celebration->cake)
            <tr>
                <td>{{ $celebration->cake->type }}</td>
                <td>{{ $celebration->cake_weight ?? '1' }} Kg
                    (AED {{ number_format($celebration->cake->price_per_kg, 2) }} per Kg)
                </td>
                <td class="price-column">{{ number_format(($celebration->cake_weight * $celebration->cake->price_per_kg), 2) }}</td>
            </tr>
        @endif
    </table>
</div>

@if($celebration->features->count() > 0)
    <div class="section">
        <div class="section-title">Additional Features</div>
        <table>
            <tr>
                <th>Feature</th>
                <th class="price-column">Price (AED)</th>
            </tr>
            @foreach($celebration->features as $feature)
                <tr>
                    <td>{{ $feature->title }}</td>
                    <td class="price-column">{{ number_format($feature->price, 2) }}</td>
                </tr>
            @endforeach
            <tr class="total-row">
                <td><strong>Additional Features Total</strong></td>
                <td class="price-column">
                    <strong>AED {{ number_format($celebration->features->sum('price'), 2) }}</strong></td>
            </tr>
        </table>
    </div>
@endif

@if($celebration->order && $celebration->order->items->count() > 0)
    <div class="section">
        <div class="section-title">Menu Selection</div>
        <table>
            <tr>
                <th>Item</th>
                <th>Quantity</th>
                <th>For</th>
                <th class="price-column">Price (AED)</th>
            </tr>
            @foreach($celebration->order->items as $item)
                <tr>
                    <td>{{ $item->menuItem->name }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ ucfirst($item->audience) }}</td>
                    <td class="price-column">
                        @if($item->audience === 'parents')
                            {{ number_format($item->menuItem->price * $item->quantity, 2) }}
                        @else
                            Included
                        @endif
                    </td>
                </tr>
                @if($item->modifiers && $item->modifiers->count() > 0)
                    @foreach($item->modifiers as $modifier)
                        <tr class="modifier-item">
                            <td>+ {{ $modifier->modifierOption->name }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td></td>
                            <td class="price-column">
                                @if($item->audience === 'parents')
                                    {{ number_format($modifier->modifierOption->price * $item->quantity, 2) }}
                                @else
                                    Included
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @endif
            @endforeach

            @if($celebration->order->items->where('audience', 'parents')->count() > 0)
                <tr class="total-row">
                    <td colspan="3"><strong>Menu Items Total</strong></td>
                    <td class="price-column">
                        <strong>AED {{ number_format($celebration->order->total_amount / 100, 2) }}</strong>
                    </td>
                </tr>
            @endif
        </table>
    </div>
@elseif($celebration->cart && $celebration->cart->items->count() > 0)
    <div class="section">
        <div class="section-title">Menu Selection</div>
        <table>
            <tr>
                <th>Item</th>
                <th>Quantity</th>
                <th>For</th>
                <th class="price-column">Price (AED)</th>
            </tr>
            @foreach($celebration->cart->items as $item)
                <tr>
                    <td>{{ $item->menuItem->name }}</td>
                    <td>{{ $item->quantity }}</td>
                    <td>{{ ucfirst($item->audience) }}</td>
                    <td class="price-column">
                        @if($item->audience === 'parents')
                            {{ number_format($item->menuItem->price * $item->quantity, 2) }}
                        @else
                            Included
                        @endif
                    </td>
                </tr>
                @if($item->modifiers && $item->modifiers->count() > 0)
                    @foreach($item->modifiers as $modifier)
                        <tr class="modifier-item">
                            <td>+ {{ $modifier->modifierOption->name }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td></td>
                            <td class="price-column">
                                @if($item->audience === 'parents')
                                    {{ number_format($modifier->modifierOption->price * $item->quantity, 2) }}
                                @else
                                    Included
                                @endif
                            </td>
                        </tr>
                    @endforeach
                @endif
            @endforeach

            @if($celebration->cart->items->where('audience', 'parents')->count() > 0)
                <tr class="total-row">
                    <td colspan="3"><strong>Menu Items Total</strong></td>
                    <td class="price-column">
                        <strong>AED {{ number_format($celebration->getCartTotalPriceAttribute(), 2) }}</strong>
                    </td>
                </tr>
            @endif
        </table>
    </div>
@endif

<div class="section">
    <div class="section-title">Children and Package Pricing</div>
    <table>
        <tr>
            <th>Package Information</th>
            <th class="price-column">Value</th>
        </tr>
        <tr>
            <td>Package Price Per Child</td>
            <td class="price-column">
                AED {{ number_format(($isWeekend ? $celebration->package->weekend_price : $celebration->package->weekday_price), 2) }}</td>
        </tr>
        <tr>
            <td>Minimum Children Required</td>
            <td class="price-column">{{ $celebration->package->min_children }}</td>
        </tr>
        <tr>
            <td>Actual Children Attending</td>
            <td class="price-column">{{ $celebration->invitations->count() }}</td>
        </tr>
        <tr class="highlighted">
            <td><strong>Children Being Charged For</strong></td>
            <td class="price-column">
                <strong>{{ max($celebration->invitations->count(), $celebration->package->min_children) }}</strong></td>
        </tr>
    </table>

    <div class="section-subtitle">Children Pricing Breakdown</div>
    <table>
        <tr>
            <th>Child Name</th>
            <th>Age</th>
            <th class="price-column">Price (AED)</th>
        </tr>
        @foreach($celebration->invitations as $child)
            <tr>
                <td>{{ $child->full_name }}</td>
                <td>{{ Carbon::parse($child->birth_date)->age ?? 'N/A' }}</td>
                <td class="price-column">
                    AED {{ number_format(($isWeekend ? $celebration->package->weekend_price : $celebration->package->weekday_price), 2) }}</td>
            </tr>
        @endforeach

        <tr class="total-row">
            <td colspan="2"><strong>Total Children Amount</strong></td>
            <td class="price-column">
                <strong>AED {{ number_format(($isWeekend ? $celebration->package->weekend_price : $celebration->package->weekday_price) * max($celebration->invitations->count(), $celebration->package->min_children), 2) }}</strong>
            </td>
        </tr>
    </table>

    @if($celebration->invitations->count() < $celebration->package->min_children)
        <div class="minimum-package-notice">
            <strong>MINIMUM PACKAGE REQUIREMENT APPLIED</strong><br>
            This package requires a minimum of {{ $celebration->package->min_children }} children.
            Since
            only {{ $celebration->invitations->count() }} {{ $celebration->invitations->count() == 1 ? 'child is' : 'children are' }}
            attending,
            you are being charged for the minimum required count.
        </div>
    @endif
</div>
<div class="section">
    <div class="section-title">Payment Summary</div>
    <table>
        <tr>
            <th>Description</th>
            <th class="price-column">Amount (AED)</th>
        </tr>
        <tr>
            <td>Package Total</td>
            <td class="price-column">{{ number_format(($isWeekend ? $celebration->package->weekend_price : $celebration->package->weekday_price) * max($celebration->invitations->count(), $celebration->package->min_children), 2) }}</td>
        </tr>
        @if($celebration->cake)
            <tr>
                <td>Cake Total</td>
                <td class="price-column">{{ number_format(($celebration->cake_weight * $celebration->cake->price_per_kg), 2) }}</td>
            </tr>
        @endif
        @if($celebration->features->count() > 0)
            <tr>
                <td>Additional Features Total</td>
                <td class="price-column">{{ number_format($celebration->features->sum('price'), 2) }}</td>
            </tr>
        @endif
        @if(($celebration->order && $celebration->order->items->where('audience', 'parents')->count() > 0) || ($celebration->cart && $celebration->cart->items->where('audience', 'parents')->count() > 0))
            <tr>
                <td>Menu Items Total</td>
                <td class="price-column">
                    @if($celebration->order)
                        {{ number_format($celebration->order->total_amount / 100, 2) }}
                    @else
                        {{ number_format($celebration->getCartTotalPriceAttribute(), 2) }}
                    @endif
                </td>
            </tr>
        @endif
        <tr class="total-row">
            <td><strong>GRAND TOTAL</strong></td>
            <td class="price-column"><strong>AED {{ number_format($celebration->total_amount / 100, 2) }}</strong></td>
        </tr>
        <tr>
            <td>Amount Paid</td>
            <td class="price-column">AED {{ number_format($celebration->paid_amount / 100, 2) }}</td>
        </tr>
        <tr class="highlighted">
            <td><strong>Amount Due</strong></td>
            <td class="price-column">
                <strong>AED {{ number_format(($celebration->total_amount - $celebration->paid_amount) / 100, 2) }}</strong>
            </td>
        </tr>
    </table>
</div>

<div class="footer">
    <div class="footer-company">Play Create Eat - Kids Amusement Arcade L.L.C</div>
    <div class="footer-message">Thank you for celebrating with us!</div>
</div>
</body>
</html>
