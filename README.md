Policy Template (Blade) Service Provider for PropAuth
==============================

This service provider, for Laravel 5+ based applications, introduces the ability to perform [PropAuth](https://github.com/psecio/propauth) evaluation checks on the current user against pre-defined policies.

### Usage

To use this provider, update your Laravel app's `app.php` configuration's "providers" section to pull in this provider:

```php
<?php
'providers' => [
	/* other providers */
	\Psecio\PropAuth\PolicyTemplateServiceProvider::class,
]
?>
```

### What else is required

This library requires two things:

- That you have the [PropAuth](https://github.com/psecio/propauth) functionality installed
- That you have policies defined in your application according to this setup: [Security Policy Evaluation in Laravel with PropAuth](http://websec.io/2015/10/07/Security-Policy-Evaluation-Laravel-PropAuth.html)

Essentially, the requirement is that there's another service provider (in the example it's the `PolicyServiceProvider`) that defines your policies in a singleton named "policies" and returns an enforcer object. For example, you could put this in `app/providers/PolicyServiceProvider.php`:

```php
<?php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Psecio\PropAuth\Enforcer;
use Psecio\PropAuth\Policy;
use Psecio\PropAuth\PolicySet;

class PolicyServiceProvider extends ServiceProvider
{
    public function register()
    {
    	$this->app->singleton('policies', function($app) {
    		$set = PolicySet::instance()
				->add('can-edit', Policy::instance()->hasUsername('ccornutt'))
			);

    		return Enforcer::instance($set);
    	});
    }
}
?>
```

This just defines the one policy, `can-edit`, where it checks the current user (pulled via `\Auth::user()`) to see if they have a `username` property of "ccornutt". With this in place, you can then use the service provider in this repo to add checks to your Blade templates.

For example, to use the `can-edit` check above you could use something like this:

```
@allows('can-edit')
they can edit!
@endallows

@denies('can-edit')
they're denied being able to edit
@enddenies
```

The two methods exposed are `@allows` and `@denies` with a required first parameter. You can also pass in optional parmeters if your `PropAuth` checks are more complex and use the closures handling. So, if your policy is defined like this:

```php
<?php
$this->app->singleton('policies', function($app) {
	$set = PolicySet::instance()
		->add('can-delete', Policy::instance()->can(function($subject, $post) {
			return $post->author == 'ccornutt';
		})
	);

	return Enforcer::instance($set);
});
?>
```

You need to pass in a value/object for `$post` in the `can-delete` closure. You can do this by giving the `@allows`/`@denies` more optional parameters:

```
@allows('can-delete', $post)
Can delete this post because the username on the post is "ccornutt"
@endallows
```


