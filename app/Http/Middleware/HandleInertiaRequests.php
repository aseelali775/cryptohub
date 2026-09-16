<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Inertia\Middleware;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        return array_merge(parent::share($request), [
            // توحيد مرجعية الـ URLs داخل الـ Frontend
            'appUrl'     => config('app.url'),
            'currentUrl' => url()->current(),
            'fullUrl'    => $request->fullUrl(),
            
            // اللغة والتوثيق
            'locale'     => app()->getLocale(),
            'auth' => [
                'user'   => $request->user(),
            ],
            
            // 🌟 إضافة رسائل النجاح والخطأ (Flash Messages) للتواصل مع Vue
            'flash' => [
                'success' => fn () => $request->session()->get('success'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ]);
    }
}