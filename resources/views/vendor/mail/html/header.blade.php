@props(['url'])
<tr>
<td class="header">
<a href="{{ $url }}" style="display: inline-block;">
@if (trim($slot) === 'Laravel')
<img src="{{ asset('images/raise-logo.png') }}" 
         alt="{{ config('app.name') }}" 
         style="height: 60px; width: auto; object-fit: contain;">
@else
{!! $slot !!}
@endif
</a>
</td>
</tr>
