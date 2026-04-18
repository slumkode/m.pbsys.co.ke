<?php

namespace App\Http\Controllers;

use App\Services\UserLoginActivityLogger;
use Illuminate\Http\Request;

class UserLocationController extends Controller
{
    protected $logger;

    public function __construct(UserLoginActivityLogger $logger)
    {
        $this->middleware('auth');
        $this->logger = $logger;
    }

    public function store(Request $request)
    {
        $this->validate($request, [
            'latitude' => 'required|numeric|between:-90,90',
            'longitude' => 'required|numeric|between:-180,180',
            'accuracy' => 'nullable|numeric|min:0|max:100000',
        ]);

        $activity = $this->logger->recordBrowserLocation(
            $request,
            $request->user(),
            $request->input('latitude'),
            $request->input('longitude'),
            $request->input('accuracy')
        );

        return response()->json([
            'status' => true,
            'activity_id' => $activity ? $activity->id : null,
        ]);
    }
}
