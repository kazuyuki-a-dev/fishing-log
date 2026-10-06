{{-- ナビは全部手書きの字（#96）。スクロールしても画面の上に残る（#108。確認の小窓 z-50 より下に重ねる） --}}
{{-- 背景は少しだけ透かし、後ろの画面はぼかす（字が重なって読みにくくならないように） --}}
<nav x-data="{ open: false }" class="sticky top-0 z-40 bg-white/85 backdrop-blur-sm font-hand">
    <!-- Primary Navigation Menu -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between h-16">
            <div class="flex">
                <!-- Logo -->
                <div class="shrink-0 flex items-center">
                    <a href="{{ auth()->check() ? route('dashboard') : url('/') }}">
                        <x-application-logo class="block h-9 w-auto fill-current text-gray-800" />
                    </a>
                </div>

            </div>

            <!-- Settings Dropdown -->
            <div class="hidden sm:flex sm:items-center sm:ms-6">
                @auth
                <x-notification-bell :count="$unreadNotifications" />
                <x-dropdown align="right" width="48">
                    <x-slot name="trigger">
                        <button class="inline-flex items-center px-3 py-2 border border-transparent text-sm leading-4 font-medium rounded-md text-gray-500 bg-transparent hover:text-gray-700 focus:outline-none transition ease-in-out duration-150">
                            <div>{{ Auth::user()->name }}</div>

                            <div class="ms-1">
                                <svg class="fill-current h-4 w-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M5.293 7.293a1 1 0 011.414 0L10 10.586l3.293-3.293a1 1 0 111.414 1.414l-4 4a1 1 0 01-1.414 0l-4-4a1 1 0 010-1.414z" clip-rule="evenodd" />
                                </svg>
                            </div>
                        </button>
                    </x-slot>

                    <x-slot name="content">
                        <x-dropdown-link :href="route('profile.edit')">
                            {{ __('Profile') }}
                        </x-dropdown-link>

                        <!-- Authentication -->
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf

                            <x-dropdown-link :href="route('logout')"
                                onclick="event.preventDefault();
                                                    this.closest('form').submit();">
                                {{ __('Log Out') }}
                            </x-dropdown-link>
                        </form>
                    </x-slot>
                </x-dropdown>
                @else
                <div class="flex items-center gap-4 text-sm">
                    <a href="{{ route('login') }}" class="text-ink hover:underline">ログイン</a>
                    <a href="{{ route('register') }}"
                        class="crayon-button">
                        会員登録
                    </a>
                </div>
                @endauth
            </div>

            <!-- Hamburger -->
            <div class="-me-2 flex items-center gap-1 sm:hidden">
                @auth
                <x-notification-bell :count="$unreadNotifications" />
                @endauth
                <button @click="open = ! open" class="inline-flex items-center justify-center p-2 rounded-md text-gray-400 hover:text-gray-500 hover:bg-gray-100 focus:outline-none focus:bg-gray-100 focus:text-gray-500 transition duration-150 ease-in-out">
                    <svg class="h-6 w-6" stroke="currentColor" fill="none" viewBox="0 0 24 24">
                        <path :class="{'hidden': open, 'inline-flex': ! open }" class="inline-flex" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16" />
                        <path :class="{'hidden': ! open, 'inline-flex': open }" class="hidden" stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                    </svg>
                </button>
            </div>
        </div>

        {{-- インデックス付箋（#108）。開いているページの付箋だけ長く出て、本文の紙とつながる --}}
        {{-- スマホでは開いているページの付箋1枚だけ（ページは ≡ メニューから選ぶ） --}}
        {{-- 幅 1280px より せまいときは、ほかの付箋はアイコンだけ（はみ出さないように。#122）。名前は title と読み上げで伝える --}}
        <div class="crayon-index-tabs">
            @foreach ($sections as $key => $section)
            @php
            $tabLabel = $section['label'] . ($key === 'admin' ? '（未対応 ' . $openReports . '件）' : '');
            @endphp
            <a href="{{ route($section['link']) }}" style="--tab: {{ $section['tab'] }};" title="{{ $tabLabel }}"
                class="crayon-index-tab {{ $currentSection === $key ? 'is-active' : '' }}"
                @if ($currentSection === $key) aria-current="page" @endif>
                <x-icon :name="$section['icon']" class="h-4 w-4 align-[-0.15em]" /><span class="crayon-index-label">{{ $tabLabel }}</span>
            </a>
            @endforeach
        </div>
    </div>

    <!-- Responsive Navigation Menu -->
    {{-- ヘッダーが上に固定なので、メニューが長いときはメニューの中だけスクロールする --}}
    <div :class="{'block': open, 'hidden': ! open}" class="hidden sm:hidden max-h-[calc(100vh-7rem)] overflow-y-auto">
        <div class="pt-2 pb-3 space-y-1">
            @auth
            <x-responsive-nav-link :href="route('dashboard')" :active="request()->routeIs('dashboard')">
                <span class="inline-block w-3 h-3 mr-2 rounded-sm align-middle" style="background: {{ config('sections.dashboard.tab') }}" aria-hidden="true"></span>{{ __('Dashboard') }}
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('planner')" :active="request()->routeIs('planner')">
                <span class="inline-block w-3 h-3 mr-2 rounded-sm align-middle" style="background: {{ config('sections.planner.tab') }}" aria-hidden="true"></span>釣行プランナー
            </x-responsive-nav-link>
            @endauth
            <x-responsive-nav-link :href="route('spots.index')" :active="request()->routeIs('spots.*')">
                <span class="inline-block w-3 h-3 mr-2 rounded-sm align-middle" style="background: {{ config('sections.spots.tab') }}" aria-hidden="true"></span>釣り場
            </x-responsive-nav-link>
            @auth
            <x-responsive-nav-link :href="route('trips.index')" :active="request()->routeIs('trips.*')">
                <span class="inline-block w-3 h-3 mr-2 rounded-sm align-middle" style="background: {{ config('sections.trips.tab') }}" aria-hidden="true"></span>釣行
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('notifications.index')" :active="request()->routeIs('notifications.*')">
                お知らせ{{ $unreadNotifications > 0 ? '（' . ($unreadNotifications > 99 ? '99+' : $unreadNotifications) . '）' : '' }}
            </x-responsive-nav-link>
            @endauth
            <x-responsive-nav-link :href="route('feed')" :active="request()->routeIs('feed')">
                <span class="inline-block w-3 h-3 mr-2 rounded-sm align-middle" style="background: {{ config('sections.feed.tab') }}" aria-hidden="true"></span>釣果フィード
            </x-responsive-nav-link>
            <x-responsive-nav-link :href="route('tools.converter')" :active="request()->routeIs('tools.*')">
                <span class="inline-block w-3 h-3 mr-2 rounded-sm align-middle" style="background: {{ config('sections.tools.tab') }}" aria-hidden="true"></span>単位変換
            </x-responsive-nav-link>
            {{-- 管理者だけ（NF-04） --}}
            @if ($openReports !== null)
            <x-responsive-nav-link :href="route('admin.reports.index')" :active="request()->routeIs('admin.*')">
                <span class="inline-block w-3 h-3 mr-2 rounded-sm align-middle" style="background: {{ config('sections.admin.tab') }}" aria-hidden="true"></span>報告（未対応 {{ $openReports }}件）
            </x-responsive-nav-link>
            @endif
            {{-- アプリの使い方（#116）。だれでも --}}
            <x-responsive-nav-link :href="route('guide')" :active="request()->routeIs('guide')">
                使い方
            </x-responsive-nav-link>
        </div>

        <!-- Responsive Settings Options -->
        @auth
        <div class="pt-4 pb-1 border-t border-gray-200">
            <div class="px-4">
                <div class="font-medium text-base text-gray-800">{{ Auth::user()->name }}</div>
                <div class="font-medium text-sm text-gray-500">{{ Auth::user()->email }}</div>
            </div>

            <div class="mt-3 space-y-1">
                <x-responsive-nav-link :href="route('profile.edit')">
                    {{ __('Profile') }}
                </x-responsive-nav-link>

                <!-- Authentication -->
                <form method="POST" action="{{ route('logout') }}">
                    @csrf

                    <x-responsive-nav-link :href="route('logout')"
                        onclick="event.preventDefault();
                                            this.closest('form').submit();">
                        {{ __('Log Out') }}
                    </x-responsive-nav-link>
                </form>
            </div>
        </div>
        @else
        <div class="pt-4 pb-3 border-t border-gray-200 space-y-1">
            <x-responsive-nav-link :href="route('login')">ログイン</x-responsive-nav-link>
            <x-responsive-nav-link :href="route('register')">会員登録</x-responsive-nav-link>
        </div>
        @endauth
    </div>
</nav>