<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Celebration Menu - {{ $celebration->family->name }}</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #000;
            line-height: 1.4;
        }
        .header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #000;
            padding-bottom: 20px;
        }
        .header h1 {
            margin: 0;
            font-size: 20px;
            font-weight: normal;
        }
        .header h2 {
            margin: 5px 0;
            font-size: 16px;
            font-weight: bold;
        }
        .celebration-info {
            margin-bottom: 30px;
            border: 1px solid #000;
            padding: 15px;
        }
        .info-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 8px;
        }
        .info-label {
            font-weight: normal;
            font-size: 12px;
        }
        .info-value {
            font-size: 14px;
            font-weight: bold;
        }
        .menu-section {
            margin-bottom: 25px;
            page-break-inside: avoid;
        }
        .menu-header {
            background: #000;
            color: #fff;
            padding: 10px;
            font-weight: normal;
            font-size: 14px;
            text-align: center;
        }
        .menu-content {
            border: 2px solid #000;
            border-top: none;
            background: #fff;
        }
        .menu-item {
            padding: 15px;
            border-bottom: 1px solid #000;
            background: #fff;
            color: #000;
        }
        .menu-item:last-child {
            border-bottom: none;
        }
        .item-name {
            font-weight: bold;
            font-size: 18px;
            margin-bottom: 6px;
            color: #000;
        }
        .item-quantity {
            font-size: 14px;
            margin-bottom: 8px;
            font-weight: bold;
            color: #000;
        }
        .item-tags {
            margin-top: 10px;
            font-size: 13px;
        }
        .tag {
            display: inline-block;
            margin-right: 8px;
            margin-bottom: 4px;
            padding: 4px 8px;
            background: #000;
            color: #fff;
            border-radius: 12px;
            font-weight: bold;
            font-size: 11px;
        }
        .modifiers {
            margin-top: 12px;
            padding-left: 15px;
            border-left: 2px solid #000;
        }
        .modifier-title {
            font-size: 11px;
            font-weight: normal;
            margin-bottom: 6px;
            color: #000;
        }
        .modifier-item {
            font-size: 13px;
            margin-bottom: 4px;
            font-weight: bold;
            color: #000;
        }
        .modifier-item:before {
            content: "- ";
        }
        .cake-section {
            border: 2px solid #000;
            padding: 15px;
            margin-bottom: 20px;
        }
        .cake-header {
            font-size: 14px;
            font-weight: normal;
            margin-bottom: 12px;
            text-align: center;
            text-transform: uppercase;
        }
        .cake-details {
            text-align: center;
        }
        .cake-detail {
            margin: 10px 0;
        }
        .cake-label {
            font-weight: normal;
            display: inline;
            font-size: 12px;
        }
        .cake-value {
            display: inline;
            margin-left: 10px;
            font-size: 16px;
            font-weight: bold;
        }
        .footer {
            margin-top: 30px;
            text-align: center;
            font-size: 10px;
            border-top: 1px solid #000;
            padding-top: 10px;
        }
        .no-items {
            text-align: center;
            padding: 30px;
            font-style: italic;
            border: 1px solid #000;
            font-size: 12px;
        }
        .included-note {
            font-style: italic;
            font-size: 10px;
            margin-top: 6px;
            color: #666;
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>CELEBRATION MENU</h1>
        <h2>{{ $celebration->family->name }} Family</h2>
    </div>

    <div class="celebration-info">
        <div class="info-row">
            <span class="info-label">Child's Name:</span>
            <span class="info-value">{{ $celebration->child->first_name }} {{ $celebration->child->last_name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Celebration Date:</span>
            <span class="info-value">{{ $dateFormatted }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Package:</span>
            <span class="info-value">{{ $celebration->package->name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Theme:</span>
            <span class="info-value">{{ $celebration->theme->name }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Total Children:</span>
            <span class="info-value">{{ $actualChildrenCount }}</span>
        </div>
        <div class="info-row">
            <span class="info-label">Parents Count:</span>
            <span class="info-value">{{ $celebration->parents_count }}</span>
        </div>
    </div>

    @if($celebration->cake)
        <div class="cake-section">
            <div class="cake-header">Selected Cake</div>
            <div class="cake-details">
                <div class="cake-detail">
                    <span class="cake-label">Type:</span>
                    <span class="cake-value">{{ $celebration->cake->type }}</span>
                </div>
                @if($celebration->cake_weight)
                    <div class="cake-detail">
                        <span class="cake-label">Weight:</span>
                        <span class="cake-value">{{ $celebration->cake_weight }} kg</span>
                    </div>
                @endif
            </div>
        </div>
    @endif

    @if($celebration->cart && $celebration->cart->items->count() > 0)
        @php
            $itemsByAudience = $celebration->cart->items->groupBy('audience');
        @endphp

        @isset($itemsByAudience['children'])
            <div class="menu-section">
                <div class="menu-header">CHILDREN'S MENU</div>
                <div class="menu-content">
                    @foreach($itemsByAudience['children'] as $item)
                        <div class="menu-item">
                            <div class="item-name">{{ $item->menuItem->name }}</div>
                            <div class="item-quantity">Quantity: {{ $item->quantity }} per child</div>
                            <div class="included-note">* Included in package price</div>

                            @if($item->menuItem->tags && $item->menuItem->tags->count() > 0)
                                <div class="item-tags">
                                    @foreach($item->menuItem->tags as $tag)
                                        <span class="tag">{{ $tag->name }}</span>
                                    @endforeach
                                </div>
                            @endif

                            @if($item->modifiers && $item->modifiers->count() > 0)
                                <div class="modifiers">
                                    <div class="modifier-title">Modifiers:</div>
                                    @foreach($item->modifiers as $modifier)
                                        <div class="modifier-item">{{ $modifier->modifierOption->name }}</div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endisset

        @isset($itemsByAudience['parents'])
            <div class="menu-section">
                <div class="menu-header">PARENTS' ADDITIONAL MENU</div>
                <div class="menu-content">
                    @foreach($itemsByAudience['parents'] as $item)
                        <div class="menu-item">
                            <div class="item-name">{{ $item->menuItem->name }}</div>
                            <div class="item-quantity">Quantity: {{ $item->quantity }}</div>

                            @if($item->menuItem->tags && $item->menuItem->tags->count() > 0)
                                <div class="item-tags">
                                    @foreach($item->menuItem->tags as $tag)
                                        <span class="tag">{{ $tag->name }}</span>
                                    @endforeach
                                </div>
                            @endif

                            @if($item->modifiers && $item->modifiers->count() > 0)
                                <div class="modifiers">
                                    <div class="modifier-title">Modifiers:</div>
                                    @foreach($item->modifiers as $modifier)
                                        <div class="modifier-item">{{ $modifier->modifierOption->name }}</div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @endisset
    @else
        <div class="no-items">
            No menu items have been selected for this celebration.
        </div>
    @endif

    <div class="footer">
        Generated on {{ now()->format('d M Y H:i') }} | Celebration ID: {{ $celebration->id }}
    </div>
</body>
</html>
