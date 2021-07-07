<?php

namespace imrancse\passportgrant;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Bridge\RefreshTokenRepository;
use Laravel\Passport\Passport;
use League\OAuth2\Server\AuthorizationServer;
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
        if(!is_null($makeGrant)){
            app(AuthorizationServer::class)->enableGrantType(
                 $makeGrant, Carbon::now()->diff(now()->addHours($access_token['lifetime']))
            );
        }
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
        
        if(!is_null($grant)){
            $refresh_token = $this->app->config->get('passport_grant_type.refresh_token', []);
            $grant->setRefreshTokenTTL(Carbon::now()->diff(now()->addDays($refresh_token['lifetime'])));
        }

        return $grant;
    }
}
