<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Symfony\Component\Process\Process;

class ConnectivityController extends Controller
{
    /**
     * Ping the given IP address.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function ping(Request $request)
    {
        Gate::authorize('manage-data');

        $request->validate([
            'ip' => 'required|string',
        ]);

        $ip = $request->input('ip');

        // Basic validation for IP or hostname to prevent command injection
        if (!filter_var($ip, FILTER_VALIDATE_IP) && !preg_match('/^[a-zA-Z0-9.-]+$/', $ip)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid IP address or hostname format.',
                'output' => 'Invalid IP format.',
            ], 422);
        }

        // Detect OS to determine ping arguments
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            // Windows: -n (count), -w (timeout in milliseconds)
            $process = new Process(['ping', '-n', '3', '-w', '2000', $ip]);
        } else {
            // Linux/Unix: -c (count), -W (timeout in seconds)
            $process = new Process(['ping', '-c', '3', '-W', '2', $ip]);
        }
        $process->run();

        $output = $process->getOutput();
        $errorOutput = $process->getErrorOutput();
        $isSuccessful = $process->isSuccessful();

        return response()->json([
            'success' => $isSuccessful,
            'ip' => $ip,
            'output' => $isSuccessful ? $output : ($errorOutput ?: $output ?: 'Ping failed with no output.'),
        ]);
    }
}
