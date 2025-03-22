<text
    x="49.2%"
    y="56.3%"
    fill="#FFD378"
    font-size="16px"
    font-family="TTFors-DemiBold"
    text-anchor="middle"
    alignment-baseline="middle"
>
    {{ $celebration->child->first_name }}
</text>
<text
    x="50%"
    y="61.49%"
    fill="#FFD378"
    font-size="16px"
    font-family="TTFors-DemiBold"
    text-anchor="middle"
    alignment-baseline="middle"
>
    is tuning {{ Illuminate\Support\Carbon::parse($celebration->child->birth_date)->age + 1 }}!
</text>
