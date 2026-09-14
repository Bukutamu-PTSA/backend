<?php

namespace App\Providers;

use App\Models\Attachment;
use App\Models\Complaint;
use App\Models\User;
use App\Observers\AuditLogObserver;
use Illuminate\Foundation\Http\Events\RequestHandled;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Audit trail otomatis (ACTIVITY_LOG) untuk entitas inti.
        Complaint::observe(AuditLogObserver::class);
        User::observe(AuditLogObserver::class);
        Attachment::observe(AuditLogObserver::class);

        Event::listen(RequestHandled::class, function (RequestHandled $event): void {
            $request = $event->request;

            if (! $request->is('api/*')) {
                return;
            }

            Log::info('API request', [
                'method' => $request->method(),
                'url' => $request->fullUrl(),
                'status' => $event->response->getStatusCode(),
            ]);
        });
    }
}
