{{--
    Pastille à initiales : « OCP Group » → OG. La couleur dépend du nom (toujours la même pour un client).
    <x-avatar :name="$client->name" size="sm" />
--}}
@props(['name', 'size' => 'md'])

@php
    $initials = \Illuminate\Support\Str::of($name)->explode(' ')->filter()->take(2)
        ->map(fn ($part) => mb_strtoupper(mb_substr($part, 0, 1)))->implode('') ?: '?';
    $palettes = [
        'from-blue-500 to-indigo-600', 'from-sky-500 to-blue-600', 'from-emerald-500 to-teal-600',
        'from-amber-500 to-orange-600', 'from-rose-500 to-pink-600', 'from-violet-500 to-purple-600',
    ];
    $palette = $palettes[crc32((string) $name) % count($palettes)];
    $sizes = ['sm' => 'h-8 w-8 text-xs', 'md' => 'h-10 w-10 text-sm', 'lg' => 'h-12 w-12 text-lg'];
@endphp

<span {{ $attributes->merge(['class' => "avatar bg-gradient-to-br text-white shadow-sm $palette ".($sizes[$size] ?? $sizes['md'])]) }} aria-hidden="true">{{ $initials }}</span>
