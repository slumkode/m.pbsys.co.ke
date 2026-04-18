<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    @include('admin.includes.meta')
    <link rel="shortcut icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">
    <link rel="icon" href="{{ asset('favicon.ico') }}" type="image/x-icon">

    <title> @yield('title') </title>

    <link href="{{ asset('assets/css/app.css') }}" rel="stylesheet">
    <link href="{{ asset('assets/css/custom.css') }}" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.16/dist/summernote.min.css" rel="stylesheet">
</head>

<body>
<div class="wrapper">

    @include('admin.includes.sidebar')
    <div class="main">
        <nav class="navbar navbar-expand navbar-light bg-white sticky-top">
            <a class="sidebar-toggle d-flex mr-3">
                <i class="align-self-center" data-feather="menu"></i>
            </a>



            <div class="navbar-collapse collapse">
                <ul class="navbar-nav ml-auto">
                    <li class="nav-item dropdown">
                        <a class="nav-icon dropdown-toggle ml-2 d-inline-block d-sm-none" href="#" id="userDropdown" data-toggle="dropdown">
                            <div class="position-relative">
                                <i class="align-middle mt-n1" data-feather="settings"></i>
                            </div>
                        </a>
                        <a class="nav-link nav-link-user dropdown-toggle d-none d-sm-inline-block" href="#" id="userDropdown" data-toggle="dropdown">
                            <img src="{{ $userimg && $userimg->access_value ? asset('uploads/'.$userimg->access_value) : asset('assets/img/avatar.png') }}" class="avatar img-fluid rounded-circle mr-1" alt="{{ Auth::user()->name }}" />
                            <span class="text-dark">{{ Auth::user()->name }}</span>
                        </a>
                        <div class="dropdown-menu dropdown-menu-right" aria-labelledby="userDropdown">
                            @if(Route::has('profile.show'))
                                <a class="dropdown-item" href="{{ route('profile.show') }}">Profile</a>
                            @endif
                            <a class="dropdown-item" href="{{ route('logout') }}"  onclick="event.preventDefault();
                            document.getElementById('logout-form').submit();">{{ __('Sign out') }}</a>
                            <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                                @csrf
                            </form>
                        </div>
                    </li>

                </ul>
            </div>
        </nav>
