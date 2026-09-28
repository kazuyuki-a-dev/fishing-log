<x-app-layout>
    <x-slot name="header">
        <h2 class="text-xl font-bold text-sea">ダッシュボード</h2>
    </x-slot>

    <div class="py-12">
        <div class="max-w-7xl mx-auto sm:px-6 lg:px-8">
            <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
                <div class="p-6 text-gray-900">
                    <a href="{{ route('planner') }}"
                        class="inline-flex items-center px-5 py-2.5 bg-float rounded-md font-bold text-sm text-white hover:bg-float-dark">
                        次の釣行をプランする
                    </a>
                </div>
            </div>
        </div>
    </div>
</x-app-layout>