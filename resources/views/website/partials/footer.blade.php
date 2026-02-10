@php $currentLocale = app()->getLocale(); @endphp
<footer class="bg-secondary text-gray-300 mt-auto">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="flex flex-col md:flex-row justify-between items-center gap-4">
            <p class="text-sm">&copy; {{ date('Y') }} {{ __('website.site_name') }}. {{ __('website.copyright') }}</p>
            <div class="flex gap-6">
                <a href="{{ route('website.home', ['locale' => $currentLocale]) }}" class="hover:text-white transition text-sm">{{ __('website.home') }}</a>
                <a href="{{ route('website.about', ['locale' => $currentLocale]) }}" class="hover:text-white transition text-sm">{{ __('website.about') }}</a>
                <a href="{{ route('website.contact', ['locale' => $currentLocale]) }}" class="hover:text-white transition text-sm">{{ __('website.contact') }}</a>
                <a href="{{ url('/login') }}" class="hover:text-white transition text-sm">{{ __('website.admin_login') }}</a>
            </div>
        </div>
    </div>
</footer>
