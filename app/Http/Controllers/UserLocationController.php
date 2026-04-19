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

    public function unavailable(Request $request)
    {
        $this->validate($request, [
            'reason' => 'required|in:permission_denied,position_unavailable,timeout,unsupported,insecure_context,unknown',
            'message' => 'nullable|string|max:255',
            'code' => 'nullable|integer',
        ]);

        $activity = $this->logger->recordBrowserLocationUnavailable(
            $request,
            $request->user(),
            $request->input('reason'),
            $request->input('message'),
            $request->input('code')
        );

        return response()->json([
            'status' => true,
            'activity_id' => $activity ? $activity->id : null,
        ]);
    }
}
