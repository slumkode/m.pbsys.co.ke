<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Providers\RouteServiceProvider;
use App\Services\UserLoginActivityLogger;
use Illuminate\Http\Request;
use Illuminate\Foundation\Auth\AuthenticatesUsers;

class LoginController extends Controller
    {
        /*
        |--------------------------------------------------------------------------
        | Login Controller
        |--------------------------------------------------------------------------
        |
        | This controller handles authenticating users for the application and
        | redirecting them to your home screen. The controller uses a trait
        | to conveniently provide its functionality to your applications.
        |
        */

        use AuthenticatesUsers;

        /**
         * Where to redirect users after login.
         *
         * @var string
         */
        protected $redirectTo   =   '/'; //RouteServiceProvider::HOME;
        protected $username     =   'email';
        /**
         * Create a new controller instance.
         *
         * @return void
         */
        public function __construct()
            {
                $this->middleware('guest')->except('logout');
            }
        public function login(Request $request)
            {
                $this->validateLogin($request);
                if ($this->hasTooManyLoginAttempts($request))
                    {
                        $this->fireLockoutEvent($request);
                        return $this->sendLockoutResponse($request);
                    }

                if ($this->attemptLogin($request))
                    {
                        return $this->sendLoginResponse($request);
                    }
                $this->incrementLoginAttempts($request);
                return $this->sendFailedLoginResponse($request);
            }
        public function validateLogin(Request $request)
            {
                $login          = $request->input('login');
                $login_type     = filter_var( $login, FILTER_VALIDATE_EMAIL ) ? 'email' : 'username';
                $request->merge([ $login_type => $login,'status'=>true]);
                if ( $login_type == 'email' )
                    {

                        $this->validate($request,   [
                            'email'    => 'required|email',
                            'password' => 'required|min:5',
                        ]);
                        $this->username = $login_type;

                    }
                else
                    {
                        unset($request->email);
                        $this->validate($request,   [
                            'username' => 'required',
                            'password' => 'required|min:5',
                        ]);
                        $this->username = $login_type;
                    }
            }
        public function attemptLogin(Request $request)
            {

                return $this->guard()->attempt(
                    $this->credentials($request), $request->filled('remember')
                );
            }
        public function credentials(Request $request)
            {
                $login          = $request->input('login');
                $login_type     = filter_var( $login, FILTER_VALIDATE_EMAIL ) ? 'email' : 'username';

                return $request->only($login_type, 'password','status');
            }

        protected function redirectTo()
            {
                $user = $this->guard()->user();

                if (! $user)
                    {
                        return '/';
                    }

                foreach ([
                    'dashboard' => '/dashboard',
                    'shortcode' => '/shortcode',
                    'services' => '/services',
                    'transaction' => '/transaction',
                    'transaction_reports' => '/transaction-reports',
                    'users' => '/users',
                    'audit_logs' => '/audit-logs',
                ] as $permission => $path)
                    {
                        if ($user->canAccessPage($permission))
                            {
                                return $path;
                            }
                    }

                return '/profile';
            }

        protected function authenticated(Request $request, $user)
            {
                $logger = app(UserLoginActivityLogger::class);
                $logger->recordLogin($request, $user, $request->filled('remember'));

                return redirect()->intended(
                    $logger->preferredRedirectUrl($user, $request, $this->redirectTo())
                );
            }
    }
