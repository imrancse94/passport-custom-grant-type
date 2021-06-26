## Installation

Note: this documentation assumes [Laravel Passport installation](https://laravel.com/docs/master/passport#introduction) is completed.

To get started, install package via the Composer package manager :

```bash
composer require imrancse/passportgrant
```

Publish the `passport_grant_type.php` configuration file using `vendor:publish` Artisan command :

```bash
php artisan vendor:publish --provider="imrancse\passportgrant\PassportGrantServiceProvider" --tag="config"
```

## Configuration

In your [config/passport_grant_type.php](https://github.com/imrancse94/passport-custom-grant-type/blob/master/config/passport_grant_type.php) configuration file, enable any custom grant types providing user provider class.

```php
// "grants" is an array of user provider class indexed by grant type

'grants' => [
    // 'otp_grant' => 'App\Passport\OTPGrantProvider',
],
```

## User provider

User provider class roles are :

* validate `/oauth/token` request custom parameters
* provide user entity instance

User provider class must implements the `imrancse\passportgrant\UserProviderInterface` :

```php
/**
 * Validate request parameters.
 *
 * @param  \Psr\Http\Message\ServerRequestInterface  $request
 * @return void
 * @throws \League\OAuth2\Server\Exception\OAuthServerException
 */
public function validate(ServerRequestInterface $request);

/**
 * Retrieve user instance from request.
 *
 * @param  \Psr\Http\Message\ServerRequestInterface  $request
 * @return mixed|null
 */
public function retrieve(ServerRequestInterface $request);
```

If request validation fails, the `validate()` method must throw a `League\OAuth2\Server\Exception\OAuthServerException` invalid parameter exception.

On success, the `retrieve()` method must return a `League\OAuth2\Server\Entities\UserEntityInterface` or `Illuminate\Contracts\Auth\Authenticatable` instance. Otherwise `null` on failure.

## User provider example

For convenience, the [UserProvider](https://github.com/imrancse94/passport-custom-grant-type/blob/master/src/UserProvider.php) class provide methods to validate and retrieve request custom parameters.

Therefore, creating a user provider becomes simple :

```php
<?php

namespace App\Passport;

use App\User;
use Psr\Http\Message\ServerRequestInterface;
use imrancse\passportgrant\UserProvider;

class OTPGrantProvider extends UserProvider
{
    /**
     * {@inheritdoc}
     */
    public function validate(ServerRequestInterface $request)
    {
        // It is not necessary to validate the "grant_type", "client_id",
        // "client_secret" and "scope" expected parameters because it is
        // already validated internally.

        $this->validateRequest($request, [
            'email' => ['required', 'email'],
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function retrieve(ServerRequestInterface $request)
    {
        $inputs = $this->only($request, [
            'email'
        ]);

        // Here insert your logic to retrieve user entity instance

        // For example, let's assume that users table has "email" column
        $user = User::where('email', $inputs['email'])->first();

        return $user;
    }
}
```

## Token request example

Request an access token for "otp_grant" grant type :

```php
// You have to import "Illuminate\Support\Facades\Http"

$response = Http::asForm()->post('https://your-app.com/oauth/token', [
                'grant_type' => 'otp_grant',
                'client_id' => <client-id>,
                'client_secret' => <client-secret>,
                'email'=>'<user-email>',
                'scope' => ''
            ],
]);
```

