<?php

namespace imrancse\passportgrant;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Bridge\RefreshTokenRepository;
use Laravel\Passport\Passport;
use League\OAuth2\Server\AuthorizationServer;
use League\OAuth2\Server\Grant\RefreshTokenGrant;
use Carbon\Carbon;
class PassportGrantServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     *
     * @return void
     */
    public function register()
    {
        $this->app
            ->afterResolving(AuthorizationServer::class, function (AuthorizationServer $server) {
                $this->makeOtpGrant($server);
            });
    }

    /**
     * Bootstrap services.
     *
     * @return void
     */
    public function boot()
    {
        $this->publishes([
            __DIR__.'/../config/passport_grant_type.php' => $this->app->configPath().'/passport_grant_type.php',
        ], 'config');
        
        $makeGrant = $this->makeOtpGrant();
        $access_token = $this->app->config->get('passport_grant_type.access_token', []);
        if(empty($access_token['lifetime'])){
            $access_token['lifetime'] = 1;
        }
        if(!is_null($makeGrant)){
            app(AuthorizationServer::class)->enableGrantType(
                 $makeGrant, Carbon::now()->diff(Carbon::now()->addHours($access_token['lifetime']))
            );
        }
        
        app(AuthorizationServer::class)->enableGrantType(
            $this->makeRefreshTokenGrant(), Carbon::now()->diff(Carbon::now()->addHours($access_token['lifetime']))
        );
    }
    
    private function makeRefreshTokenGrant()
    {
        $repository = $this->app->make(RefreshTokenRepository::class);
        return tap(new RefreshTokenGrant($repository), function ($grant) {
            $refresh_token = $this->app->config->get('passport_grant_type.refresh_token', []);
            if(empty($refresh_token['lifetime'])){
                $refresh_token['lifetime'] = 90;
            }
            $grant->setRefreshTokenTTL(Carbon::now()->diff(Carbon::now()->addDays($refresh_token['lifetime'])));
        });
    }
    
    protected function makeOtpGrant()
    {
        $grant = null;
        $grants = $this->app->config->get('passport_grant_type.grants', []);
        foreach ($grants as $grantType => $userProviderClass) {
            $grant = new OTPGrant(
               $grantType,
               $this->app->make($userProviderClass),
               $this->app->make(RefreshTokenRepository::class)
            );
        }
        $refresh_token = $this->app->config->get('passport_grant_type.refresh_token', []);
        if(empty($refresh_token['lifetime'])){
            $refresh_token['lifetime'] = 90;
        }
        if(!is_null($grant) && !empty($refresh_token['lifetime'])){
            $grant->setRefreshTokenTTL(Carbon::now()->diff(Carbon::now()->addDays($refresh_token['lifetime'])));
        }

        return $grant;
    }
}
