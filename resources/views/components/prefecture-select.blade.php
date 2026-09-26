@props(['selected' => null, 'placeholder' => false])

<select {{ $attributes->merge(['class' => 'border-gray-300 focus:border-sea-500 focus:ring-sea-500 rounded-md shadow-sm']) }}>
    @if ($placeholder)
    <option value="">選択してください</option>
    @endif
    @foreach (config('prefectures') as $prefecture)
    <option value="{{ $prefecture }}" @selected($selected===$prefecture)>{{ $prefecture }}</option>
    @endforeach
</select>