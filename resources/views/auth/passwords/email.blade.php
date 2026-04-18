<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="utf-8">
    <meta http-equiv="X-UA-Compatible" content="IE=edge">
    <meta name="viewport" content="width=device-width, initial-scale=1, shrink-to-fit=no">
    <title>Paper - Responsive Admin Template</title>

    <link href="{{ asset('assets/css/app.css') }}" rel="stylesheet">

</head>

<body>
<main class="main h-100 w-100">
    <div class="container h-100">
        <div class="row h-100">
            <div class="col-sm-10 col-md-8 col-lg-6 mx-auto d-table h-100">
                <div class="d-table-cell align-middle">

                    <div class="text-center mt-4">
                        <h1 class="h2">Reset password</h1>
                        <p class="lead">
                            Enter your email to reset your password.
                        </p>
                    </div>

                    <div class="card">
                        <div class="card-body">
                            @if (session('status'))
                                <div class="alert alert-success" role="alert">
                                    {{ session('status') }}
                                </div>
                            @endif
                            <div class="m-sm-4">
                                <form  method="POST" action="{{ route('password.email') }}">
                                    <div class="form-group">
                                        <label>{{ __('E-Mail Address') }}</label>
                                        <input class="form-control form-control-  @error('email') is-invalid @enderror " type="email" name="email" placeholder="Enter your email" />
                                        @error('email')
                                            <span class="invalid-feedback" role="alert">
                                                <strong>{{ $message }}</strong>
                                            </span>
                                        @enderror
                                    </div>
                                    <div class="text-center mt-3">
                                       <button type="submit" class="btn btn-lg btn-primary">Reset password</button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                </div>
            </div>
        </div>
    </div>
</main>

<script src="{{ asset('assets/js/app.js') }}"></script>
</body>

</html>
