@include('admin.includes.header')
<main class="content">
    <div class="container-fluid p-0">

        <h1 class="h3 mb-3">@yield('subtitle')</h1>

        <div class="row">
            <div class="col-12">
               @yield('content')
            </div>
        </div>

    </div>
</main>
@include('admin.includes.footer')
