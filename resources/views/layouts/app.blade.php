<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">

        <title>@yield('page_title', setting('site.title') . " - " . setting('site.description'))</title>

        <!-- Fonts -->
        <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Nunito:wght@400;600;700&display=swap">

        <!-- Styles -->
        <link rel="stylesheet" href="{{ mix('css/app.css') }}">
        <link rel="stylesheet" href="{{ asset('template/plugins/fontawesome-free/css/all.min.css') }}">

        @livewireStyles
        @stack('css')
        <link rel="stylesheet" href="{{ asset('template/plugins/toastr/toastr.min.css') }}">
        <link rel="stylesheet" href="{{ asset('template/plugins/select2/css/select2.min.css') }}">
        <link rel="stylesheet" href="{{ asset('template/css/adminlte.min.css') }}">

        <link rel="stylesheet" href="{{ asset('template/plugins/summernote/summernote-bs4.min.css') }}">
        <link rel="stylesheet" href="{{ asset('template/plugins/animation/animate.min.css') }}">

        <link rel="stylesheet" href="{{ asset('template/css/custom.css') }}">
        
        <!-- Scripts -->
        <!-- firebase -->
        <script src="https://www.gstatic.com/firebasejs/8.6.2/firebase-app.js"></script>
        <script src="https://www.gstatic.com/firebasejs/8.6.2/firebase-analytics.js"></script>
        <script src="https://www.gstatic.com/firebasejs/8.6.2/firebase-auth.js"></script>
        <script src="https://www.gstatic.com/firebasejs/8.6.2/firebase-firestore.js"></script>
        
        <script src="{{ mix('js/app.js') }}" defer></script>
        
    </head>
    <body class="font-sans antialiased">
        <x-jet-banner />

        <div class="min-h-screen bg-gray-100">
            @livewire('navigation-menu')

            <!-- Page Heading -->
            <div class="content-wrapper">
                <div class="content-header p-0 mb-3">
                    <div class="container-fluid pl-0 pr-0">
                        @if (isset($header))
                            <header class="bg-cyan shadow">
                                <div class="max-w-7xl mx-auto py-6 px-4 sm:px-6 lg:px-8">
                                    {{ $header }}
                                </div>
                            </header>
                        @endif
                    </div>
                </div>

                <div class="content">
                    <div class="container-fluid">
                    {{ $slot }}
                    </div>
                </div>
            </div>
            <a id="back-to-top" href="#" class="btn btn-primary back-to-top" role="button" aria-label="Scroll to top">
                <i class="fas fa-chevron-up"></i>
            </a>
        </div>
        @include("homes.layouts.footer")

        @stack('modals')

        @livewireScripts
        <script src="{{ asset('template/plugins/jquery/jquery.min.js') }}"></script>
        <!-- Bootstrap -->
        <script src="{{ asset('template/plugins/bootstrap/js/bootstrap.bundle.min.js') }}"></script>
        <!-- AdminLTE -->
        <script src="{{ asset('template/plugins/toastr/toastr.min.js') }}"></script>
        <!-- Select2 -->
        <script src="{{ asset('template/plugins/select2/js/select2.full.min.js')}}"></script>
        <script src="{{ asset('/template/plugins/inputmask/jquery.inputmask.min.js')}}"></script>
        <script src="{{ asset('template/dist/js/adminlte.js') }}"></script>
        <script src="{{ asset('template/plugins/summernote/summernote-bs4.min.js') }}"></script>
        <script src="{{ asset('template/js/jqueryform.min.js') }}"></script>
        <script src="{{ asset('js/custom.js') }}"></script>
        @stack('scripts')

    </body>
</html>
