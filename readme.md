# Mako ReCaptcha

Google reCaptcha Package for Mako Framework 4.0.

## Install

Use composer to install. Simply add package to your project.

```php
composer require aldoanizio/makorecaptcha:*
```

So now you can update your project and install package with a single command.

```php
composer update
```


### Register Service

After installing you'll have to register a new service in your ``app/config/application.php`` file.

```php
    /**
     * Services to register in the dependecy injection container.
     */

    'services' =>
    [
        ....
        'makorecaptcha\service\ReCaptchaService',
    ],
```

### Configuring

There are two ways to configure your package. The first is editing config file directly in packages folder: ``app/packages/makorecaptcha/config/config.php`` and input the necessary information.

If you like you can copy the package's config file ``app/packages/makorecaptcha/config/config.php`` into ``app/config/packages/makorecaptcha`` folder and the application will load that file instead of the one located in the package. This makes it possible to update the package while keeping your custom settings.

## Assign variable template

Before use make use of captcha is needed to assign the makoReCaptcha instance to a new variable in template views.

```php
$this->view->assign('makoReCaptcha', $this->makoReCaptcha);
```


## Display Captcha Form

To make the reCAPTCHA widget appear when your page loads, simply call the ``html`` method in your <form> element.


```php
<form method="post" action="/my/route">

    <?php echo $makoReCaptcha->html(); ?>

    <input type="submit" value"Check Captcha" />

</form>
```


## Validate submited data

To verify submited data you'll need to use the ``check`` method parsing 3 parameters.

The first parameter is the requested address ip.

The second parameter is the auto-generated ``recaptcha_challenge_field`` field.

The third parameter is the ``recaptcha_response_field`` wich contains the input data.


```php
$captcha = $this->makoReCaptcha->check($this->request->server('REMOTE_ADDR'), $this->request->post('recaptcha_challenge_field'), $this->request->post('recaptcha_response_field'));
```

Now you need to check if the input data is valid using the ``isValid`` method.

```php
if($captcha->isValid())
{
    // Lets Go!!
}
else
{
    // Wow, hang on dude!!
}
```

You can also use the ``isInvalid`` method.

```php
if($captcha->isInvalid())
{
    // Wow, hang on dude!!
}
else
{
    // Lets Go!!
}
```

