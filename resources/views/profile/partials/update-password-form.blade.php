<section>
    {{-- たまにしか使わないので、ふだんは閉じておき、押すと開く（#215） --}}
    {{-- 入力のまちがいでエラーになったときと、変えたあとは、開いたまま結果を見せる --}}
    @php($passwordOpen = $errors->updatePassword->isNotEmpty() || session('status') === 'password-updated')
    <details @if ($passwordOpen) open @endif>
        <summary class="cursor-pointer">
            <span class="crayon-subheading">パスワードを変える</span>
            <span class="mt-1 block text-sm text-sand">今のパスワードを覚えているときに、ここで変えられます。忘れたときは、ログインの画面の「パスワードを忘れた方」から。</span>
        </summary>

        <form method="post" action="{{ route('password.update') }}" class="mt-6 space-y-6">
            @csrf
            @method('put')

            <div>
                <x-input-label for="update_password_current_password" :value="__('Current Password')" />
                <x-text-input id="update_password_current_password" name="current_password" type="password" class="mt-1 block w-full" autocomplete="current-password" />
                <x-input-error :messages="$errors->updatePassword->get('current_password')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="update_password_password" :value="__('New Password')" />
                <x-text-input id="update_password_password" name="password" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                <x-input-error :messages="$errors->updatePassword->get('password')" class="mt-2" />
            </div>

            <div>
                <x-input-label for="update_password_password_confirmation" :value="__('Confirm Password')" />
                <x-text-input id="update_password_password_confirmation" name="password_confirmation" type="password" class="mt-1 block w-full" autocomplete="new-password" />
                <x-input-error :messages="$errors->updatePassword->get('password_confirmation')" class="mt-2" />
            </div>

            <div class="flex items-center justify-end gap-4">

                @if (session('status') === 'password-updated')
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
    </details>
</section>
