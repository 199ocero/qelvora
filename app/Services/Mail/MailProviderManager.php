<?php

namespace App\Services\Mail;

use App\Enums\MailProvider;
use App\Exceptions\UnsupportedProviderException;
use App\Models\ProviderConnection;
use App\Services\Mail\Contracts\MailProviderDriver;
use App\Services\Mail\Drivers\Ses\SesClientFactory;
use App\Services\Mail\Drivers\Ses\SesDriver;
use Closure;

/**
 * Resolves the concrete {@see MailProviderDriver} for a team's provider
 * connection. Mirrors Laravel's own manager pattern: SES is implemented, other
 * providers throw until a driver is registered, and tests can swap in a fake.
 */
class MailProviderManager
{
    /**
     * Custom driver factories keyed by provider value.
     *
     * @var array<string, Closure(ProviderConnection): MailProviderDriver>
     */
    protected array $extensions = [];

    /**
     * A fake driver factory used to bypass real providers in tests.
     *
     * @var (Closure(ProviderConnection): MailProviderDriver)|null
     */
    protected ?Closure $fake = null;

    public function __construct(
        protected SesClientFactory $sesClientFactory,
    ) {
        //
    }

    /**
     * Resolve the driver for the given connection.
     */
    public function driver(ProviderConnection $connection): MailProviderDriver
    {
        if ($this->fake !== null) {
            return ($this->fake)($connection);
        }

        if (isset($this->extensions[$connection->provider->value])) {
            return ($this->extensions[$connection->provider->value])($connection);
        }

        return match ($connection->provider) {
            MailProvider::Ses => new SesDriver($connection, $this->sesClientFactory),
            default => throw UnsupportedProviderException::for($connection->provider),
        };
    }

    /**
     * Register a custom driver factory for a provider.
     *
     * @param  Closure(ProviderConnection): MailProviderDriver  $factory
     */
    public function extend(MailProvider $provider, Closure $factory): void
    {
        $this->extensions[$provider->value] = $factory;
    }

    /**
     * Force every resolved driver to be the given fake (tests only).
     *
     * @param  MailProviderDriver|Closure(ProviderConnection): MailProviderDriver  $driver
     */
    public function fake(MailProviderDriver|Closure $driver): void
    {
        $this->fake = $driver instanceof Closure
            ? $driver
            : fn (ProviderConnection $connection): MailProviderDriver => $driver;
    }
}
