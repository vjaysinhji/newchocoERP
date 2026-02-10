<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" dir="{{ \App\Helpers\WebsiteSettings::isRtl(app()->getLocale()) ? 'rtl' : 'ltr' }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', config('app.name', 'ChocoERP'))</title>
    <meta name="description" content="@yield('description', __('website.tagline'))">
    {{-- Tailwind CSS CDN - lightweight, responsive --}}
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        primary: '#7c3aed',
                        secondary: '#0f172a',
                    }
                }
            }
        }
    </script>
</head>
<body class="antialiased text-gray-900 bg-white">
    @include('website.partials.header')
    <main class="min-h-screen">
        @yield('content')
    </main>
    @include('website.partials.footer')
</body>
</html>
