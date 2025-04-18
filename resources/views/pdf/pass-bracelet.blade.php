<!doctype html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        @page {
            size: 25.4mm 254mm;
            margin: 0;
        }

        body {
            margin: 0;
        }

        .wrap {
            position: relative;
            width: 25.4mm;
            height: 254mm;
        }

        .qr {
            position: absolute;
            width: 24mm;
            height: 24mm;
            left: calc((25.4mm - 24mm) / 2);
            bottom: 78pt;
        }

        .logo-long {
            position: absolute;
            width: 17mm;
            height: 150mm;
            left: calc((25.4mm - 17mm) / 2);
            bottom: calc(78pt + 24mm + 10mm);
        }

        .logo {
            position: absolute;
            width: 22mm;
            height: calc((22mm * 20) / 24);
            left: calc((25.4mm - 22mm) / 2);
            bottom: calc(78pt + 24mm + 10mm + 150mm + 5mm);
        }
    </style>
</head>
<body>
<div class="wrap">
    <img src="{{ $qr }}" class="qr">
    <img src="{{ $logoLong }}" class="logo-long">
    <img src="{{ $logo }}" class="logo">
</div>
</body>
</html>
