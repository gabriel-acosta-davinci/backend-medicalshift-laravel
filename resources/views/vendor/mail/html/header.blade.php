@props(['url'])
<tr>
<td class="header" style="text-align: center;">
@if (str_contains($slot, '<img') || str_contains($slot, 'logo.png'))
<div style="text-align: center;">
{!! $slot !!}
</div>
@elseif (trim($slot) === 'Laravel')
<img src="https://laravel.com/img/notification-logo-v2.1.png" class="logo" alt="Laravel Logo">
@else
<a href="{{ $url }}" style="display: inline-block;">
{!! $slot !!}
</a>
@endif
</td>
</tr>
