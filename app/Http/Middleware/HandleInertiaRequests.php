<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     *
     * @var string
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        return [
            ...parent::share($request),
            'ziggy' => fn() => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'flash' => [
                'success' => $request->session()->get('success'),
                'error' => $request->session()->get('error'),
                'warning' => $request->session()->get('warning'),
                'modbusResult' => $request->session()->get('modbusResult'),
                'weatherResult' => $request->session()->get('weatherResult'),
            ],
            'auth' => [
                'user' => $request->user() ? [
                    'id' => $request->user()->id,
                    'name' => $request->user()->name,
                    'roles' => $request->user()->roles->pluck('name'),
                ] : null,
                'can' => $request->user() ? [
                    'admin' => $request->user()->can('admin-only'),
                    'manage' => $request->user()->can('manage-data'),
                    'create' => $request->user()->can('can-create'),
                    'read' => $request->user()->can('can-read'),
                    'update' => $request->user()->can('can-update'),
                    'delete' => $request->user()->can('can-delete'),
                ] : [],
            ],
        ];
    }
}
