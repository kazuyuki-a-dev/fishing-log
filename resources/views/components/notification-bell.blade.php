{{-- お知らせのベルマークと未読件数（FN-18）。PC とスマホの両方のナビで使う --}}
@props(['count'])

@php
$label = $count > 99 ? '99+' : $count;
@endphp

<a href="{{ route('notifications.index') }}" {{ $attributes->merge(['class' => 'relative p-2 text-gray-500 hover:text-gray-700']) }}
    aria-label="お知らせ{{ $count ? '（未読 ' . $label . ' 件）' : '' }}">
    <svg class="h-6 w-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true">
        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.4-1.4A2 2 0 0118 14.2V11a6 6 0 00-4-5.7V5a2 2 0 10-4 0v.3A6 6 0 006 11v3.2a2 2 0 01-.6 1.4L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9" />
    </svg>
    @if ($count > 0)
    <span class="absolute top-0 right-0 min-w-5 h-5 px-1 rounded-full bg-red-600 text-white text-xs font-bold flex items-center justify-center">{{ $label }}</span>
    @endif
</a>
