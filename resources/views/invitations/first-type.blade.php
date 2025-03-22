<!doctype html>
<html lang="en">
<head>
    <style>
        @font-face {
            font-family: 'TTFors-DemiBold';
            src: url('{{ asset('fonts/TT Fors/TT_Fors_DemiBold.ttf') }}') format('truetype');
            font-weight: 600;
        }

        @font-face {
            font-family: 'TTFors-Regular';
            src: url('{{ asset('fonts/TT Fors/TT_Fors_Regular.ttf') }}') format('truetype');
            font-weight: 400;
        }
    </style>
</head>
<body>
<x-invitations.first-type.background>
    <x-slot name="header">
        <x-invitations.stars/>
        <x-invitations.play-create-eat/>
    </x-slot>

    <x-invitations.you-are-invited/>
    <x-invitations.first-type.birthday-party/>
    <x-invitations.tuning/>
    <x-invitations.date/>
    <x-invitations.address/>
</x-invitations.first-type.background>
</body>
</html>
