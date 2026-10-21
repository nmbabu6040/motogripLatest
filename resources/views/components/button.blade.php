<button
    {{ $attributes->merge(['type' => 'submit', 'class' => 'inline-flex items-center justify-center rounded-control bg-navy px-5 py-2.5 font-semibold text-white hover:bg-industrial']) }}>{{ $slot }}</button>
