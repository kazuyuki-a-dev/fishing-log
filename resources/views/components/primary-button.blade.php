<button {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center px-5 py-2.5 bg-float border border-transparent rounded-md font-bold text-sm text-white hover:bg-float-dark focus:bg-float-dark active:bg-float-dark focus:outline-none focus:ring-2 focus:ring-float focus:ring-offset-2 transition ease-in-out duration-150']) }}>
    {{ $slot }}
</button>