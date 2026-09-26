<x-guest-layout>
    <form method="POST" action="{{ route('register') }}">
        @csrf

        <!-- Name -->
        <div>
            <x-input-label for="name" value="ニックネーム" />
            <x-text-input id="name" class="block mt-1 w-full" type="text" name="name" :value="old('name')" required autofocus autocomplete="name" />
            <p class="mt-1 text-sm text-gray-600">ほかのユーザーに表示されます。本名は入れないでください。</p>
            <x-input-error :messages="$errors->get('name')" class="mt-2" />
        </div>

        <!-- Main Field (Prefecture) -->
        <div class="mt-4">
            <x-input-label for="home_prefecture" value="メインフィールド（よく行く都道府県）" />
            <select id="home_prefecture" name="home_prefecture" required
                class="block mt-1 w-full border-gray-300 focus:border-indigo-500 focus:ring-indigo-500 rounded-md shadow-sm">
                <option value="">選択してください</option>
                @foreach (config('prefectures') as $prefecture)
                <option value="{{ $prefecture }}" @selected(old('home_prefecture')===$prefecture)>{{ $prefecture }}</option>
                @endforeach
            </select>
            <x-input-error :messages="$errors->get('home_prefecture')" class="mt-2" />
        </div>

        <!-- Email Address -->
        <div class="mt-4">
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" class="block mt-1 w-full" type="email" name="email" :value="old('email')" required autocomplete="username" />
            <x-input-error :messages="$errors->get('email')" class="mt-2" />
        </div>

        <!-- Password -->
        <div class="mt-4">
            <x-input-label for="password" :value="__('Password')" />

            <x-text-input id="password" class="block mt-1 w-full"
                type="password"
                name="password"
                required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password')" class="mt-2" />
        </div>

        <!-- Confirm Password -->
        <div class="mt-4">
            <x-input-label for="password_confirmation" :value="__('Confirm Password')" />

            <x-text-input id="password_confirmation" class="block mt-1 w-full"
                type="password"
                name="password_confirmation" required autocomplete="new-password" />

            <x-input-error :messages="$errors->get('password_confirmation')" class="mt-2" />
        </div>

        <!-- Terms -->
        <div class="mt-4">
            <label for="terms" class="inline-flex items-start">
                <input id="terms" type="checkbox" name="terms" value="1" required @checked(old('terms'))
                    class="mt-1 rounded border-gray-300 text-indigo-600 shadow-sm focus:ring-indigo-500">
                <span class="ms-2 text-sm text-gray-600">
                    <a href="{{ route('terms') }}" target="_blank" class="underline hover:text-gray-900">利用規約</a>と<a href="{{ route('privacy') }}" target="_blank" class="underline hover:text-gray-900">プライバシーポリシー</a>に同意します
                </span>
            </label>
            <x-input-error :messages="$errors->get('terms')" class="mt-2" />
        </div>

        <div class="flex items-center justify-end mt-4">
            <a class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500" href="{{ route('login') }}">
                {{ __('Already registered?') }}
            </a>

            <x-primary-button class="ms-4">
                {{ __('Register') }}
            </x-primary-button>
        </div>
    </form>
</x-guest-layout>