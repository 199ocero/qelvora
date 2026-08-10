<?php

namespace App\Console\Commands;

use App\Enums\IdentityStatus;
use App\Enums\ProviderConnectionStatus;
use App\Jobs\RefreshMailIdentity;
use App\Models\MailIdentity;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;

class RefreshMailIdentities extends Command
{
    protected $signature = 'mail:refresh-identities';

    protected $description = 'Re-check pending SES identities so their status updates without a user visiting the page';

    /**
     * How long to keep polling an unverified identity before leaving it to a
     * manual re-check. SES DNS propagation can take up to 72 hours.
     */
    private const MAX_AGE_HOURS = 72;

    /**
     * Skip identities checked more recently than this, so we do not re-hit the
     * provider right after a user manually re-checked.
     */
    private const MIN_RECHECK_MINUTES = 5;

    public function handle(): int
    {
        $identities = MailIdentity::query()
            ->whereIn('status', [
                IdentityStatus::NotStarted,
                IdentityStatus::Pending,
                IdentityStatus::TemporaryFailure,
            ])
            ->where('created_at', '>=', now()->subHours(self::MAX_AGE_HOURS))
            ->where(function (Builder $query) {
                $query->whereNull('last_checked_at')
                    ->orWhere('last_checked_at', '<=', now()->subMinutes(self::MIN_RECHECK_MINUTES));
            })
            ->whereHas('connection', function (Builder $query) {
                $query->where('is_active', true)
                    ->where('status', ProviderConnectionStatus::Connected);
            })
            ->get();

        $identities->each(fn (MailIdentity $identity) => RefreshMailIdentity::dispatch($identity));

        $this->info("Queued {$identities->count()} identity re-check(s).");

        return self::SUCCESS;
    }
}
