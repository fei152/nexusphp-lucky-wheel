<?php

namespace NexusPlugin\LuckyWheel\Http\Controllers;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use NexusPlugin\LuckyWheel\Services\LuckyWheelService;

class LuckyWheelController extends Controller
{
    public function __construct(private readonly LuckyWheelService $service)
    {
    }

    public function index()
    {
        /** @var User $user */
        $user = Auth::guard('nexus-web')->user();

        return view('lucky-wheel::index', [
            'user' => $user,
            'config' => $this->service->getPublicConfig($user),
            'prizes' => $this->service->listPublicPrizes(),
            'logs' => $this->service->listRecentLogs(),
            'myLogs' => $this->service->listUserLogs($user),
        ]);
    }

    public function spin(Request $request): JsonResponse
    {
        try {
            /** @var User $user */
            $user = Auth::guard('nexus-web')->user();
            $result = $this->service->spin($user);

            return response()->json($result);
        } catch (\Throwable $throwable) {
            return response()->json([
                'message' => $throwable->getMessage(),
            ], 422);
        }
    }
}
