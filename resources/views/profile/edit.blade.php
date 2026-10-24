<x-app-layout>
    <x-slot name="header">
        <h2 class="crayon-heading">
            {{ __('Profile') }}
        </h2>
    </x-slot>

    <div class="py-12">
        {{-- ほかの画面と同じく、カードを細くして真ん中に置く（中身が左に寄らないように。#215） --}}
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8 space-y-6">
            {{-- お試し用アカウントは、変更と退会ができない（だれでもログインできるため。#136） --}}
            @if ($user->isDemo())
            <div class="crayon-note p-4 sm:p-6 text-sm space-y-1">
                <p class="font-bold">お試し用のアカウントです</p>
                <p>いろいろな人が使うので、ニックネーム・メールアドレス・パスワードの変更と、退会はできません。釣行の記録・編集・削除は、自由に試せます。本物の個人情報は入れないでください。</p>
            </div>
            @else
            <div class="p-4 sm:p-8 crayon-card">
                @include('profile.partials.update-profile-information-form')
            </div>

            <div class="p-4 sm:p-8 crayon-card">
                @include('profile.partials.update-password-form')
            </div>

            <div class="p-4 sm:p-8 crayon-card">
                @include('profile.partials.delete-user-form')
            </div>
            @endif
        </div>
    </div>
</x-app-layout>
