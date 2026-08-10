<?php

namespace App\Services\Mail\Dns;

use App\Services\Mail\Data\DnsRecord;
use Throwable;

/**
 * Best-effort public-DNS lookup that tells, per record, whether it is already
 * visible in DNS. This is separate from (and usually faster than) the
 * provider's own verification, so a user who just published their records can
 * see them go live before SES reports the identity as verified.
 *
 * Records that are not yet published resolve to NXDOMAIN, and PHP's built-in
 * dns_get_record() has no timeout, so a handful of not-yet-live records can
 * block a web request past its execution limit. To stay bounded we send our
 * own UDP DNS queries with a hard per-query timeout and an overall time budget.
 * Any failure only marks a record as missing (or leaves it unknown once the
 * budget is spent); it must never bubble up and fail the surrounding request.
 */
class DnsRecordChecker
{
    public const STATUS_SEEN = 'seen';

    public const STATUS_MISSING = 'missing';

    private const TYPE_CNAME = 5;

    private const TYPE_MX = 15;

    private const TYPE_TXT = 16;

    /**
     * @param  float  $timeout  Per-query socket timeout, in seconds.
     * @param  float  $budget  Overall time budget for one annotate() call, in seconds.
     * @param  list<string>  $resolvers  Public DNS resolvers to query, tried in order.
     */
    public function __construct(
        private readonly float $timeout = 1.5,
        private readonly float $budget = 8.0,
        private readonly array $resolvers = ['1.1.1.1', '8.8.8.8'],
    ) {
        //
    }

    /**
     * Return copies of the records with their `status` set to seen/missing.
     *
     * Once the time budget is spent, remaining records are left unknown (null)
     * rather than blocking; the next re-check will resolve them.
     *
     * @param  array<int, DnsRecord>  $records
     * @return array<int, DnsRecord>
     */
    public function annotate(array $records): array
    {
        $deadline = microtime(true) + $this->budget;

        return array_map(
            function (DnsRecord $record) use ($deadline): DnsRecord {
                if (microtime(true) >= $deadline) {
                    return $record->withStatus(null);
                }

                return $record->withStatus(
                    $this->isVisible($record, $deadline) ? self::STATUS_SEEN : self::STATUS_MISSING,
                );
            },
            $records,
        );
    }

    /**
     * Whether the record can already be resolved in public DNS.
     */
    protected function isVisible(DnsRecord $record, float $deadline): bool
    {
        $type = match (strtoupper($record->type)) {
            'CNAME' => self::TYPE_CNAME,
            'MX' => self::TYPE_MX,
            'TXT' => self::TYPE_TXT,
            default => null,
        };

        if ($type === null) {
            return false;
        }

        $expected = $this->normalize($record->value);

        try {
            foreach ($this->query($record->host, $type, $deadline) as $answer) {
                if ($this->normalize($answer) === $expected) {
                    return true;
                }
            }
        } catch (Throwable) {
            return false;
        }

        return false;
    }

    /**
     * Resolve a name/type over UDP and return the matching answers as strings
     * (CNAME/MX targets or TXT values). Returns an empty array on any failure.
     *
     * @return list<string>
     */
    protected function query(string $host, int $type, float $deadline): array
    {
        $packet = $this->buildQuery($host, $type);

        foreach ($this->resolvers as $resolver) {
            $remaining = $deadline - microtime(true);

            if ($remaining <= 0) {
                break;
            }

            $response = $this->exchange($resolver, $packet, min($this->timeout, $remaining));

            if ($response !== null) {
                return $this->parseAnswers($response, $type);
            }
        }

        return [];
    }

    /**
     * Send one UDP query and read the response, or null on socket failure/timeout.
     */
    protected function exchange(string $resolver, string $packet, float $timeout): ?string
    {
        $socket = @stream_socket_client("udp://{$resolver}:53", $errno, $errstr, $timeout);

        if ($socket === false) {
            return null;
        }

        $seconds = (int) floor($timeout);
        stream_set_timeout($socket, $seconds, (int) (($timeout - $seconds) * 1_000_000));

        @fwrite($socket, $packet);
        $response = @fread($socket, 65535);
        fclose($socket);

        return is_string($response) && strlen($response) >= 12 ? $response : null;
    }

    /**
     * Build a DNS query packet for a single question (class IN).
     */
    protected function buildQuery(string $host, int $type): string
    {
        // Header: random id, recursion desired, one question, one additional (EDNS0 OPT).
        $header = random_bytes(2)."\x01\x00".pack('n', 1)."\x00\x00\x00\x00".pack('n', 1);

        $qname = '';

        foreach (explode('.', trim($host, '.')) as $label) {
            $qname .= chr(strlen($label)).$label;
        }

        $qname .= "\x00";

        $question = $qname.pack('n', $type).pack('n', 1);

        // EDNS0 OPT record advertising a 4096-byte UDP payload, so full replies
        // arrive in one datagram instead of being truncated at 512 bytes.
        $opt = "\x00".pack('n', 41).pack('n', 4096)."\x00\x00\x00\x00".pack('n', 0);

        return $header.$question.$opt;
    }

    /**
     * Parse answer records of the requested type into their string values.
     *
     * @return list<string>
     */
    protected function parseAnswers(string $packet, int $wantType): array
    {
        $length = strlen($packet);
        $answerCount = $this->readUint16($packet, 6);

        // Skip the header and the single question.
        [, $offset] = $this->readName($packet, 12);
        $offset += 4;

        $results = [];

        for ($i = 0; $i < $answerCount; $i++) {
            [, $offset] = $this->readName($packet, $offset);

            if ($offset + 10 > $length) {
                break;
            }

            $type = $this->readUint16($packet, $offset);
            $rdLength = $this->readUint16($packet, $offset + 8);
            $rdata = $offset + 10;

            if ($type === $wantType) {
                $value = $this->readRdata($packet, $type, $rdata, $rdLength);

                if ($value !== null) {
                    $results[] = $value;
                }
            }

            $offset = $rdata + $rdLength;
        }

        return $results;
    }

    /**
     * Extract the comparable value from a record's rdata.
     */
    protected function readRdata(string $packet, int $type, int $rdata, int $rdLength): ?string
    {
        return match ($type) {
            self::TYPE_CNAME => $this->readName($packet, $rdata)[0],
            self::TYPE_MX => $this->readName($packet, $rdata + 2)[0],
            self::TYPE_TXT => $this->readTxt($packet, $rdata, $rdLength),
            default => null,
        };
    }

    /**
     * Concatenate the length-prefixed character strings that make up a TXT record.
     */
    protected function readTxt(string $packet, int $rdata, int $rdLength): string
    {
        $text = '';
        $position = $rdata;
        $end = $rdata + $rdLength;

        while ($position < $end && $position < strlen($packet)) {
            $partLength = ord($packet[$position]);
            $text .= substr($packet, $position + 1, $partLength);
            $position += 1 + $partLength;
        }

        return $text;
    }

    /**
     * Read a (possibly compressed) domain name, returning [name, offsetAfterName].
     *
     * @return array{0: string, 1: int}
     */
    protected function readName(string $packet, int $offset): array
    {
        $labels = [];
        $length = strlen($packet);
        $jumped = false;
        $next = $offset;
        $guard = 0;

        while ($offset < $length && $guard++ < 128) {
            $len = ord($packet[$offset]);

            if ($len === 0) {
                if (! $jumped) {
                    $next = $offset + 1;
                }
                break;
            }

            if (($len & 0xC0) === 0xC0) {
                if ($offset + 1 >= $length) {
                    break;
                }

                if (! $jumped) {
                    $next = $offset + 2;
                }

                $offset = (($len & 0x3F) << 8) | ord($packet[$offset + 1]);
                $jumped = true;

                continue;
            }

            $labels[] = substr($packet, $offset + 1, $len);
            $offset += 1 + $len;
        }

        return [implode('.', $labels), $next];
    }

    /**
     * Read a big-endian unsigned 16-bit integer at the given offset.
     */
    protected function readUint16(string $packet, int $offset): int
    {
        $unpacked = unpack('n', substr($packet, $offset, 2));

        return $unpacked === false ? 0 : $unpacked[1];
    }

    /**
     * Lowercase and drop any trailing dot so hostnames compare equal.
     */
    protected function normalize(string $value): string
    {
        return strtolower(rtrim(trim($value), '.'));
    }
}
