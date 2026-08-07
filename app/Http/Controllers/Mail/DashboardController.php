<?php

namespace App\Http\Controllers\Mail;

use App\Concerns\PresentsMailResources;
use App\Enums\IdentityStatus;
use App\Http\Controllers\Controller;
use App\Models\Team;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    use PresentsMailResources;

    /**
     * Show the email analytics overview.
     */
    public function __invoke(Request $request): Response
    {
        $team = $request->user()->currentTeam;

        Gate::authorize('viewEmails', $team);

        return Inertia::render('mail/Dashboard', [
            'connection' => ($connection = $team->activeConnection())
                ? $this->presentConnection($connection)
                : null,
            'stats' => $this->stats($team),
            'trend' => $this->trend($team),
            'counts' => [
                'verifiedIdentities' => $team->mailIdentities()->where('status', IdentityStatus::Verified)->count(),
                'suppressions' => $team->suppressions()->count(),
                'apiKeys' => $team->apiKeys()->active()->count(),
            ],
        ]);
    }

    /**
     * 30-day sending totals and rates.
     *
     * @return array<string, mixed>
     */
    protected function stats(Team $team): array
    {
        $since = now()->subDays(30);

        $totals = $team->emailMessages()
            ->where('created_at', '>=', $since)
            ->selectRaw("sum(case when status in ('sent','delivered','bounced','complained') then 1 else 0 end) as sent")
            ->selectRaw("sum(case when status = 'delivered' then 1 else 0 end) as delivered")
            ->selectRaw("sum(case when status = 'bounced' then 1 else 0 end) as bounced")
            ->selectRaw("sum(case when status = 'complained' then 1 else 0 end) as complained")
            ->first();

        $sent = (int) ($totals->sent ?? 0);
        $delivered = (int) ($totals->delivered ?? 0);
        $bounced = (int) ($totals->bounced ?? 0);
        $complained = (int) ($totals->complained ?? 0);

        return [
            'sent' => $sent,
            'delivered' => $delivered,
            'bounced' => $bounced,
            'complained' => $complained,
            'deliveryRate' => $this->rate($delivered, $sent),
            'bounceRate' => $this->rate($bounced, $sent),
            'complaintRate' => $this->rate($complained, $sent),
        ];
    }

    /**
     * A 14-day daily trend of sends, deliveries and failures.
     *
     * @return array<int, array{date: string, sent: int, delivered: int, failed: int}>
     */
    protected function trend(Team $team): array
    {
        $start = now()->subDays(13)->startOfDay();

        $rows = $team->emailMessages()
            ->where('created_at', '>=', $start)
            ->selectRaw('date(created_at) as day')
            ->selectRaw("sum(case when status in ('sent','delivered','bounced','complained') then 1 else 0 end) as sent")
            ->selectRaw("sum(case when status = 'delivered' then 1 else 0 end) as delivered")
            ->selectRaw("sum(case when status in ('bounced','complained','failed','rejected') then 1 else 0 end) as failed")
            ->groupBy('day')
            ->get()
            ->keyBy('day');

        return collect(range(0, 13))
            ->map(function (int $offset) use ($start, $rows) {
                $day = $start->copy()->addDays($offset)->toDateString();
                $row = $rows->get($day);

                return [
                    'date' => $day,
                    'sent' => (int) ($row->sent ?? 0),
                    'delivered' => (int) ($row->delivered ?? 0),
                    'failed' => (int) ($row->failed ?? 0),
                ];
            })
            ->all();
    }

    /**
     * A percentage rate rounded to two decimals.
     */
    protected function rate(int $part, int $total): float
    {
        return $total === 0 ? 0.0 : round(($part / $total) * 100, 2);
    }
}
