<?php

namespace App\Providers\Filament;

use App\Services\PassService;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Navigation\NavigationGroup;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken;
use Illuminate\Http\Request;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Route;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        $panel = $panel
            ->default()
            ->id('admin')
            ->path('admin')
            ->favicon(asset('images/favicon.ico'))
            ->brandLogo(asset('images/logo.svg'))
            ->login()
            ->colors([
                'primary' => Color::Amber,
            ])
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\\Filament\\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\\Filament\\Pages')
            ->discoverClusters(in: app_path('Filament/Clusters'), for: 'App\\Filament\\Clusters')
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\\Filament\\Widgets')
            ->navigationGroups([
                NavigationGroup::make()
                    ->label('Menu Management')
                    ->icon('heroicon-o-cake')
                    ->collapsible(),
                NavigationGroup::make()
                    ->label('Celebration Management')
                    ->icon('heroicon-o-gift')
                    ->collapsible(),
                NavigationGroup::make()
                    ->label('User Management')
                    ->icon('heroicon-o-users')
                    ->collapsible(),
                NavigationGroup::make()
                    ->label('Admin Management')
                    ->icon('heroicon-o-shield-check')
                    ->collapsible(),
                NavigationGroup::make()
                    ->label('Product Management')
                    ->icon('heroicon-o-ticket')
                    ->collapsible(),
                NavigationGroup::make()
                    ->label('Content Management')
                    ->icon('heroicon-o-document-text')
                    ->collapsible(),
            ])
            ->widgets([
                Widgets\AccountWidget::class,
                Widgets\FilamentInfoWidget::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                VerifyCsrfToken::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->authMiddleware([
                Authenticate::class,
            ])
            ->authGuard('admin');

        return $this->customRoutes($panel);
    }

    protected function customRoutes(Panel $panel): Panel
    {
        return $panel->routes(function () {
            Route::get('pass/print', function (Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse {
                $payload = $request->validate([
                    'serial' => ['required', 'exists:passes,serial'],
                ]);

                $serial = $payload['serial'];
                $pdfFile = app(PassService::class)->getBraceletPdfPath($serial);

                return response()->file($pdfFile, [
                    'Content-Type'        => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="pass-' . $serial . '.pdf"',
                ]);
            })
                ->name('pass.print');
        });

    }
}
