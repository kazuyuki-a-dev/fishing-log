@props(['options' => [], 'selected' => null, 'placeholder' => null, 'labels' => []])

<select {{ $attributes->merge(['class' => 'border-gray-300 focus:border-sea-500 focus:ring-sea-500 rounded-md shadow-sm']) }}>
    @if ($placeholder !== null)
    <option value="">{{ $placeholder }}</option>
    @endif
    @foreach ($options as $option)
    <option value="{{ $option }}" @selected((string) $selected===(string) $option)>{{ $labels[$option] ?? $option }}</option>
    @endforeach
</select>