<?php

namespace imrancse\passportgrant;

use Illuminate\Support\ServiceProvider;
use Laravel\Passport\Bridge\RefreshTokenRepository;
use Laravel\Passport\Passport;
use League\OAuth2\Server\AuthorizationServer;

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
        
        app(AuthorizationServer::class)->enableGrantType(
            $this->makeOtpGrant(), Passport::tokensExpireIn()
        );
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
        $grant->setRefreshTokenTTL(Passport::refreshTokensExpireIn());

        return $grant;
    }
}
