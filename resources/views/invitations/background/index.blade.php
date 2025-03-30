<!doctype html>
<html lang="en">
<head>
    <style>
        @font-face {
            font-family: 'TTFors-Black';
            src: url('{{ asset('fonts/TT_Fors/TT_Fors_Black.ttf') }}') format('truetype');
            font-weight: 900;
        }
        @font-face {
            font-family: 'TTFors-ExtraBold';
            src: url('{{ asset('fonts/TT_Fors/TT_Fors_ExtraBold.ttf') }}') format('truetype');
            font-weight: 800;
        }
        @font-face {
            font-family: 'TTFors-DemiBold';
            src: url('{{ asset('fonts/TT_Fors/TT_Fors_DemiBold.ttf') }}') format('truetype');
            font-weight: 600;
        }
        @font-face {
            font-family: 'TTFors-Regular';
            src: url('{{ asset('fonts/TT_Fors/TT_Fors_Regular.ttf') }}') format('truetype');
            font-weight: 400;
        }
    </style>
</head>
<body>
<svg width="310" height="428" viewBox="0 0 310 428" fill="none" xmlns="http://www.w3.org/2000/svg">
    <rect width="310" height="428" fill="{{ $invitation->background_color }}"/>
    <x-invitations.decorations/>
    <x-invitations.play-create-eat :invitation="$invitation"/>
    <x-invitations.you-are-invited color="{{ $invitation->text_color }}"/>
    <x-invitations.child-name :invitaion="$invitation"/>
    <x-invitations.birthday-party color="{{ $invitation->text_color }}"/>
    <x-invitations.date :invitation="$invitation"/>
    <x-invitations.place :invitation="$invitation"/>
</svg>
</body>
</html>
