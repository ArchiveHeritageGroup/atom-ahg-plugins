<?php

namespace AhgFederation;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * "Join the network" requests (#151, parity with heratio#1203): a public form
 * lands a request as pending; an administrator moves it to reviewing,
 * approved or declined. Approving is a label only. Adding the institution as
 * a federation peer stays a deliberate step on the peers page.
 */
class JoinRequestService
{
    public const TABLE = 'federation_join_request';
    public const STATUSES = ['pending', 'reviewing', 'approved', 'declined'];

    /** @return int|string the new id, or an error message */
    public function submit(array $d)
    {
        $name = self::clip($d['institution_name'] ?? null, 255);
        if (!$name) {
            return 'Please give the name of your institution.';
        }
        $email = self::clip($d['contact_email'] ?? null, 255);
        if ($email && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return 'That email address does not look right.';
        }
        $url = self::clip($d['base_url'] ?? null, 1024);
        if ($url && (!filter_var($url, FILTER_VALIDATE_URL) || !preg_match('#^https?://#i', $url))) {
            return 'The website address must start with http:// or https://.';
        }
        $now = date('Y-m-d H:i:s');

        return (int) DB::table(self::TABLE)->insertGetId([
            'institution_name' => $name,
            'contact_name' => self::clip($d['contact_name'] ?? null, 255),
            'contact_email' => $email,
            'base_url' => $url,
            'what_they_share' => self::clip($d['what_they_share'] ?? null, 10000),
            'notes' => self::clip($d['notes'] ?? null, 10000),
            'status' => 'pending',
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /** @return array{requests: array, counts: array<string,int>} */
    public function queue(?string $status = null): array
    {
        $q = DB::table(self::TABLE)
            ->orderByRaw("FIELD(status, 'pending', 'reviewing', 'approved', 'declined')")
            ->orderByDesc('id')->limit(1000);
        if (in_array($status, self::STATUSES, true)) {
            $q->where('status', $status);
        }
        $counts = array_fill_keys(self::STATUSES, 0);
        foreach (DB::table(self::TABLE)->select('status', DB::raw('COUNT(*) AS c'))->groupBy('status')->get() as $r) {
            $counts[$r->status] = (int) $r->c;
        }

        return ['requests' => $q->get()->all(), 'counts' => $counts];
    }

    public function setStatus(int $id, string $status, string $by, ?string $note = null): bool
    {
        $row = DB::table(self::TABLE)->where('id', $id)->first();
        if (!$row || !in_array($status, self::STATUSES, true)) {
            return false;
        }
        $now = date('Y-m-d H:i:s');
        $notes = trim((string) $row->notes);
        if (null !== $note && '' !== trim($note)) {
            $notes = ltrim($notes."\n[{$now} {$by}] ".trim($note));
        }

        return false !== DB::table(self::TABLE)->where('id', $id)->update([
            'status' => $status, 'notes' => '' === $notes ? null : $notes,
            'reviewed_by' => self::clip($by, 255), 'reviewed_at' => $now, 'updated_at' => $now,
        ]);
    }

    private static function clip($v, int $max): ?string
    {
        $v = trim((string) $v);

        return '' === $v ? null : mb_substr($v, 0, $max);
    }
}
