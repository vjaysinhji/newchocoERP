@php
    $currentLocale = app()->getLocale();
    $supportedLocales = config('website.supported_locales', []);
@endphp
<header class="bg-white shadow-sm sticky top-0 z-50">
    <nav class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex justify-between items-center h-16">
            <a href="{{ route('website.home', ['locale' => $currentLocale]) }}" class="text-xl font-bold text-primary">{{ __('website.site_name') }}</a>
            <div class="hidden md:flex items-center gap-4">
                <a href="{{ route('website.home', ['locale' => $currentLocale]) }}" class="text-gray-600 hover:text-primary transition">{{ __('website.home') }}</a>
                <a href="{{ route('website.about', ['locale' => $currentLocale]) }}" class="text-gray-600 hover:text-primary transition">{{ __('website.about') }}</a>
                <a href="{{ route('website.contact', ['locale' => $currentLocale]) }}" class="text-gray-600 hover:text-primary transition">{{ __('website.contact') }}</a>
                <a href="{{ url('/login') }}" class="text-gray-600 hover:text-primary transition">{{ __('website.admin_login') }}</a>
                {{-- Language Switcher --}}
                <div class="relative group">
                    <button type="button" class="flex items-center gap-1 px-3 py-1.5 rounded-lg text-gray-600 hover:bg-gray-100 transition" id="lang-toggle" aria-label="Language">
                        <span class="text-sm font-medium">{{ $supportedLocales[$currentLocale]['name'] ?? $currentLocale }}</span>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </button>
                    <div class="absolute right-0 mt-1 py-2 w-36 bg-white rounded-lg shadow-lg border border-gray-100 hidden group-hover:block">
                        @foreach($supportedLocales as $code => $config)
                            <a href="{{ route('website.home', ['locale' => $code]) }}" class="block px-4 py-2 text-sm text-gray-700 hover:bg-gray-50 {{ $code === $currentLocale ? 'font-semibold text-primary' : '' }}">
                                {{ $config['name'] }}
                            </a>
                        @endforeach
                    </div>
                </div>
            </div>
            <button type="button" class="md:hidden p-2 rounded-lg text-gray-600 hover:bg-gray-100" id="mobile-menu-btn" aria-label="Menu">
                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
            </button>
        </div>
        <div id="mobile-menu" class="hidden md:hidden pb-4">
            <a href="{{ route('website.home', ['locale' => $currentLocale]) }}" class="block py-2 text-gray-600 hover:text-primary">{{ __('website.home') }}</a>
            <a href="{{ route('website.about', ['locale' => $currentLocale]) }}" class="block py-2 text-gray-600 hover:text-primary">{{ __('website.about') }}</a>
            <a href="{{ route('website.contact', ['locale' => $currentLocale]) }}" class="block py-2 text-gray-600 hover:text-primary">{{ __('website.contact') }}</a>
            <a href="{{ url('/login') }}" class="block py-2 text-gray-600 hover:text-primary">{{ __('website.admin_login') }}</a>
            @foreach($supportedLocales as $code => $config)
                <a href="{{ route('website.home', ['locale' => $code]) }}" class="block py-2 text-gray-600 hover:text-primary">{{ $config['name'] }}</a>
            @endforeach
        </div>
    </nav>
</header>
<script>
    document.getElementById('mobile-menu-btn')?.addEventListener('click', function() {
        document.getElementById('mobile-menu').classList.toggle('hidden');
    });
</script>
