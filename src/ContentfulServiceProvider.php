<?php

/**
 * This file is part of the contentful/laravel package.
 *
 * @copyright 2015-2020 Contentful GmbH
 * @license   MIT
 */

declare(strict_types=1);

namespace Contentful\Laravel;

use Contentful\Core\Api\IntegrationInterface;
use Contentful\Delivery\Client;
use Contentful\Delivery\ClientOptions;
use Illuminate\Foundation\Application;
use Illuminate\Support\ServiceProvider;

class ContentfulServiceProvider extends ServiceProvider implements IntegrationInterface
{
    /**
     * Indicates if loading of the provider is deferred.
     *
     * @var bool
     */
    protected $defer = true;

    /**
     * Register any other events for your application.
     */
    public function boot()
    {
        $configFile = (string) \realpath(__DIR__.'/config/contentful.php');

        $this->publishes([
            $configFile => $this->app->make('path.config').'/contentful.php',
        ]);

        $this->mergeConfigFrom($configFile, 'contentful');
    }

    /**
     * Register the service provider.
     */
    public function register()
    {
        $this->app->singleton(Client::class, function (Application $app): Client {
            $config = $app['config']['contentful'];

            $options = new ClientOptions();
            if ($config['delivery.preview']) {
                $options->usingPreviewApi();
            }

            if ($config['delivery.defaultLocale']) {
                $options->withDefaultLocale($config['delivery.defaultLocale']);
            }

            if (\is_callable($config['delivery.options'])) {
                ($config['delivery.options'])($options, $app);
            }

            $client = new Client(
                $config['delivery.token'],
                $config['delivery.space'],
                $config['delivery.environment'],
                $options
            );
            $client->useIntegration($this);

            return $client;
        });
    }

    /**
     * {@inheritdoc}
     */
    public function provides()
    {
        return [Client::class];
    }

    /**
     * {@inheritdoc}
     */
    public function getIntegrationName(): string
    {
        return 'contentful.laravel';
    }

    /**
     * {@inheritdoc}
     */
    public function getIntegrationPackageName(): string
    {
        return 'contentful/laravel';
    }
}
