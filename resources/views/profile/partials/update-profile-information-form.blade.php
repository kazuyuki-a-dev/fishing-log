<section>
    <header>
        <h2 class="crayon-subheading">
            {{ __('Profile Information') }}
        </h2>

        <p class="mt-1 text-sm text-gray-600">
            {{ __("Update your account's profile information and email address.") }}
        </p>
    </header>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-6 space-y-6">
        @csrf
        @method('patch')

        <div>
            <x-input-label for="name" value="ニックネーム" />
            <x-text-input id="name" name="name" type="text" class="mt-1 block w-full" :value="old('name', $user->name)" required autofocus autocomplete="name" />
            <p class="mt-1 text-sm text-gray-600">ほかのユーザーに表示されます。本名は入れないでください。</p>
            <x-input-error class="mt-2" :messages="$errors->get('name')" />
        </div>

        <div>
            <x-input-label for="home_prefecture" value="メインフィールド（よく行く都道府県）" />
            <x-prefecture-select id="home_prefecture" name="home_prefecture" class="mt-1 block w-full"
                :selected="old('home_prefecture', $user->home_prefecture)" required />
            <x-input-error class="mt-2" :messages="$errors->get('home_prefecture')" />
        </div>

        <div>
            {{-- チェックを外すと何も送られないので、先に 0 を送っておく（チェックがあれば 1 で上書き） --}}
            <input type="hidden" name="notify_enabled" value="0">
            <label for="notify_enabled" class="inline-flex items-start">
                <input id="notify_enabled" type="checkbox" name="notify_enabled" value="1"
                    @checked(old('notify_enabled', $user->notify_enabled))
                    class="mt-1 rounded border-gray-300 text-sea-600 shadow-sm focus:ring-sea-500">
                <span class="ms-2 text-sm text-gray-600">メインフィールドの県で釣果や釣り場が公開されたら、お知らせを受け取る</span>
            </label>
            <x-input-error class="mt-2" :messages="$errors->get('notify_enabled')" />
        </div>

        <div>
            <x-input-label for="email" :value="__('Email')" />
            <x-text-input id="email" name="email" type="email" class="mt-1 block w-full" :value="old('email', $user->email)" required autocomplete="username" />
            <x-input-error class="mt-2" :messages="$errors->get('email')" />

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
            <div>
                <p class="text-sm mt-2 text-gray-800">
                    {{ __('Your email address is unverified.') }}

                    <button form="send-verification" class="underline text-sm text-gray-600 hover:text-gray-900 rounded-md focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-sea-500">
                        {{ __('Click here to re-send the verification email.') }}
                    </button>
                </p>

                @if (session('status') === 'verification-link-sent')
                <p class="mt-2 font-medium text-sm text-green-600">
                    {{ __('A new verification link has been sent to your email address.') }}
                </p>
                @endif
            </div>
            @endif
        </div>

        <div class="flex items-center justify-end gap-4">
            @if (session('status') === 'profile-updated')
            <p
            x-data="{ show: true }"
            x-show="show"
            x-transition
            x-init="setTimeout(() => show = false, 2000)"
            class="text-sm text-gray-600">{{ __('Saved.') }}</p>
            @endif
            <x-primary-button>{{ __('Save') }}</x-primary-button>
        </div>
    </form>
</section>