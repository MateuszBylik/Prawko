<button {{ $attributes->merge(['type' => 'submit', 'class' => 'btn bg-rose-600 text-white hover:bg-rose-700 focus-visible:ring-rose-500']) }}>
    {{ $slot }}
</button>
