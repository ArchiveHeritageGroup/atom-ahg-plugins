<?php

namespace AhgFederation;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Loan requests between federation members (#151, parity with heratio#1203).
 *
 * Same table and workflow as Heratio's federation_loan_request. On PSIS the
 * members are federation peers: requesting_member_id / holding_member_id hold
 * a federation_peer.id, and 0 means this institution. One side is always this
 * institution, so a request is either incoming (a peer asks to borrow from us)
 * or outgoing (we ask a peer). item_ref holds the local information_object id
 * (our record, or the harvested copy of the peer's record) when there is one.
 */
class LoanRequestService
{
    public const TABLE = 'federation_loan_request';
    public const SELF = 0;

    public const STATUSES = ['requested', 'approved', 'declined', 'in_transit', 'returned', 'cancelled'];

    public const TRANSITIONS = [
        'requested' => ['approved', 'declined', 'cancelled'],
        'approved' => ['in_transit', 'cancelled'],
        'in_transit' => ['returned'],
        'declined' => [],
        'returned' => [],
        'cancelled' => [],
    ];

    /** @return array<int,string> peer id => name */
    public function peers(): array
    {
        return DB::table('federation_peer')->orderBy('name')->pluck('name', 'id')->all();
    }

    /**
     * @param array $d direction ('incoming'|'outgoing'), peer_id, item_ref, item_title, purpose, needed_from, needed_to, notes
     *
     * @return int|string the new id, or an error message
     */
    public function create(array $d)
    {
        $peer = (int) ($d['peer_id'] ?? 0);
        if (!DB::table('federation_peer')->where('id', $peer)->exists()) {
            return 'Choose the other institution.';
        }
        $incoming = 'incoming' === ($d['direction'] ?? '');
        $from = self::date($d['needed_from'] ?? null);
        $to = self::date($d['needed_to'] ?? null);
        if ($from && $to && $to < $from) {
            return 'The loan cannot end before it starts.';
        }
        $ref = trim((string) ($d['item_ref'] ?? ''));
        $title = self::clip($d['item_title'] ?? null, 1024);
        if ('' !== $ref && !$title) {
            $title = self::recordTitle((int) $ref);
        }
        if ('' === $ref && !$title) {
            return 'Say which item is wanted.';
        }
        $now = date('Y-m-d H:i:s');

        return (int) DB::table(self::TABLE)->insertGetId([
            'requesting_member_id' => $incoming ? $peer : self::SELF,
            'holding_member_id' => $incoming ? self::SELF : $peer,
            'item_ref' => '' === $ref ? null : self::clip($ref, 255),
            'item_title' => $title,
            'purpose' => self::clip($d['purpose'] ?? null, 2048),
            'status' => 'requested',
            'needed_from' => $from,
            'needed_to' => $to,
            'notes' => self::clip($d['notes'] ?? null, 65535),
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function find(int $id): ?object
    {
        $row = DB::table(self::TABLE)->where('id', $id)->first();

        return $row ? $this->decorate($row, $this->peers()) : null;
    }

    public function list(string $status = '', string $direction = ''): array
    {
        $q = DB::table(self::TABLE);
        if (in_array($status, self::STATUSES, true)) {
            $q->where('status', $status);
        }
        if ('incoming' === $direction) {
            $q->where('holding_member_id', self::SELF);
        } elseif ('outgoing' === $direction) {
            $q->where('requesting_member_id', self::SELF);
        }
        $peers = $this->peers();

        return array_map(fn ($r) => $this->decorate($r, $peers), $q->orderByDesc('id')->limit(500)->get()->all());
    }

    /** @return array<string,int> status => count, plus 'all' */
    public function statusCounts(): array
    {
        $counts = array_fill_keys(self::STATUSES, 0);
        foreach (DB::table(self::TABLE)->select('status', DB::raw('COUNT(*) AS c'))->groupBy('status')->get() as $r) {
            $counts[$r->status] = (int) $r->c;
        }

        return $counts + ['all' => array_sum($counts)];
    }

    /** Move a request on, if TRANSITIONS allows it, recording who and when. */
    public function transition(int $id, string $to, string $by, ?string $note = null): bool
    {
        $row = DB::table(self::TABLE)->where('id', $id)->first();
        if (!$row || !in_array($to, self::TRANSITIONS[$row->status] ?? [], true)) {
            return false;
        }
        $now = date('Y-m-d H:i:s');
        $notes = trim((string) $row->notes);
        if (null !== $note && '' !== trim($note)) {
            $notes = ltrim($notes."\n[{$now} {$row->status} -> {$to}] ".trim($note));
        }

        return (bool) DB::table(self::TABLE)->where('id', $id)->where('status', $row->status)->update([
            'status' => $to, 'notes' => '' === $notes ? null : $notes,
            'decided_by' => self::clip($by, 255), 'decided_at' => $now, 'updated_at' => $now,
        ]);
    }

    /**
     * Loan analytics (heratio#1203 analytics slice): direction, approval rate,
     * days from request to decision, busiest partners.
     */
    public function report(): array
    {
        $rows = DB::table(self::TABLE)->get(['requesting_member_id', 'holding_member_id', 'status', 'created_at', 'decided_at'])->all();
        $peers = $this->peers();
        $out = ['incoming' => 0, 'outgoing' => 0, 'approved' => 0, 'declined' => 0, 'rate_pct' => null, 'avg_days' => null, 'partners' => []];
        $days = [];
        foreach ($rows as $r) {
            $incoming = self::SELF === (int) $r->holding_member_id;
            ++$out[$incoming ? 'incoming' : 'outgoing'];
            $partner = $incoming ? (int) $r->requesting_member_id : (int) $r->holding_member_id;
            $name = $peers[$partner] ?? "#{$partner}";
            $out['partners'][$name] = ($out['partners'][$name] ?? 0) + 1;
            // Approved counts every request that got past approval.
            if (in_array($r->status, ['approved', 'in_transit', 'returned'], true)) {
                ++$out['approved'];
            } elseif ('declined' === $r->status) {
                ++$out['declined'];
            }
            if ($r->decided_at && $r->created_at && 'requested' !== $r->status) {
                $days[] = (strtotime($r->decided_at) - strtotime($r->created_at)) / 86400;
            }
        }
        $decided = $out['approved'] + $out['declined'];
        $out['rate_pct'] = $decided ? (int) round(100 * $out['approved'] / $decided) : null;
        $out['avg_days'] = $days ? round(array_sum($days) / count($days), 1) : null;
        arsort($out['partners']);
        $out['partners'] = array_slice($out['partners'], 0, 8, true);

        return $out;
    }

    private function decorate(object $row, array $peers): object
    {
        $name = fn ($id) => self::SELF === (int) $id ? 'This institution' : ($peers[(int) $id] ?? "#{$id}");
        $row->requesting_name = $name($row->requesting_member_id);
        $row->holding_name = $name($row->holding_member_id);
        $row->direction = self::SELF === (int) $row->holding_member_id ? 'incoming' : 'outgoing';
        $row->item_slug = ctype_digit((string) $row->item_ref)
            ? DB::table('slug')->where('object_id', (int) $row->item_ref)->value('slug') : null;

        return $row;
    }

    public static function recordTitle(int $id): ?string
    {
        if ($id < 1) {
            return null;
        }
        $t = DB::table('information_object as io')
            ->join('information_object_i18n as i', fn ($j) => $j->on('i.id', '=', 'io.id')->on('i.culture', '=', 'io.source_culture'))
            ->where('io.id', $id)->value('i.title');

        return $t ? self::clip($t, 1024) : null;
    }

    private static function clip($v, int $max): ?string
    {
        $v = trim((string) $v);

        return '' === $v ? null : mb_substr($v, 0, $max);
    }

    private static function date($v): ?string
    {
        $ts = '' === trim((string) $v) ? false : strtotime((string) $v);

        return false === $ts ? null : date('Y-m-d', $ts);
    }
}
