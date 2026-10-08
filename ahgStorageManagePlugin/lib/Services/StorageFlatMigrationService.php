<?php

namespace AhgStorageManage\Services;

use Exception;
use Illuminate\Database\Capsule\Manager as DB;

/**
 * Carry the flat location fields into the location tree.
 *
 * Before the tree existed, where a box was kept was written down in three
 * places, none of which knew about the others:
 *
 *  - physical_object_extended: building, floor, room, aisle, bay, rack, shelf;
 *  - ahg_strongroom, through ahg_physical_object_storage: one named room;
 *  - physical_object_i18n.location: a line of free text.
 *
 * This reads them in that order of trust and, for each physical object, finds or
 * creates the chain of locations it names and places the object in the innermost
 * one. The placement goes through StorageMovementService, so it is a first
 * placement in the movement log like any other, with a note saying where it came
 * from.
 *
 * Nothing is deleted and nothing is overwritten. The flat fields stay exactly as
 * they were, an object that already has a place in the tree is left alone, and a
 * location that already exists is reused, so the whole thing can be run again
 * after more boxes are catalogued and only the new ones move.
 *
 * Free text is not guessed at. "Delmas" might be a town, a building or a shelf
 * mark, and a tree built from guesses has to be taken apart by hand. An object
 * with nothing but free text is reported and left, unless the archive says what
 * its free text means with the free_text_type option.
 *
 * run(false) changes nothing and returns what run(true) would do.
 */
class StorageFlatMigrationService
{
    /** physical_object_extended columns, outermost first, with the type each becomes. */
    public const LEVELS = ['building', 'floor', 'room', 'aisle', 'bay', 'rack', 'shelf'];

    public const NOTE = 'Migrated from the flat location fields';
    public const ACTOR = 'storage:migrate-flat-locations';

    protected string $culture;
    protected StorageLocationService $locations;
    protected StorageMovementService $movements;

    /** @var array<string, int> "parent|type|name" => location id; negative while only planned */
    protected array $known = [];
    protected int $planned = 0;
    protected bool $apply = false;
    protected array $report = [];

    public function __construct(string $culture = 'en')
    {
        $this->culture = $culture;
        $this->locations = new StorageLocationService($culture);
        $this->movements = new StorageMovementService($culture);
    }

    /**
     * @param bool  $apply   false: report only, write nothing
     * @param array $options free_text_type: a location type to file free-text-only
     *                       objects under, as a root location named by the text
     *
     * @return array locations_created, locations_reused, placed, skipped, each a
     *               list of rows a person can read
     */
    public function run(bool $apply = false, array $options = []): array
    {
        $freeTextType = $options['free_text_type'] ?? null;

        if (null !== $freeTextType && !array_key_exists($freeTextType, $this->locations->types())) {
            throw new Exception('Unknown location type for free text: '.$freeTextType);
        }

        $this->apply = $apply;
        $this->known = [];
        $this->planned = 0;
        $this->report = ['locations_created' => [], 'locations_reused' => [], 'placed' => [], 'skipped' => []];

        $work = function () use ($freeTextType) {
            $strongrooms = $this->strongrooms();

            // Every strongroom becomes a room, whether or not anything is in it:
            // an empty room is still a room the archive has.
            foreach ($strongrooms['rooms'] as $room) {
                $this->resolve([['room', $room['name']]], $room);
            }

            foreach ($this->objects() as $object) {
                $id = (int) $object['id'];
                $name = $object['name'] ?: 'Object '.$id;

                if (null !== $this->movements->currentLocationOf($id)) {
                    $this->report['skipped'][] = ['object_id' => $id, 'name' => $name, 'reason' => 'already has a place in the tree'];

                    continue;
                }

                $path = [];
                $source = null;
                $room = $strongrooms['of'][$id] ?? null;

                foreach (self::LEVELS as $level) {
                    $value = trim((string) ($object[$level] ?? ''));

                    if ('' !== $value) {
                        $path[] = [$level, $value];
                    }
                }

                if ($path) {
                    $source = 'structured fields';

                    if (null !== $room) {
                        $source .= ' (also assigned to strongroom "'.$room.'", not used)';
                    }
                } elseif (null !== $room) {
                    $path = [['room', $room]];
                    $source = 'strongroom';
                } elseif ('' !== trim((string) ($object['location'] ?? ''))) {
                    if (null === $freeTextType) {
                        $this->report['skipped'][] = [
                            'object_id' => $id, 'name' => $name,
                            'reason' => 'free text only: "'.trim((string) $object['location']).'"',
                        ];

                        continue;
                    }

                    $path = [[$freeTextType, trim((string) $object['location'])]];
                    $source = 'free text';
                } else {
                    $this->report['skipped'][] = ['object_id' => $id, 'name' => $name, 'reason' => 'no location recorded'];

                    continue;
                }

                $locationId = $this->resolve($path);

                if ($this->apply) {
                    $this->movements->moveObject($id, $locationId, [
                        'note' => self::NOTE.' ('.$source.')',
                        'user_id' => null,
                        'username' => self::ACTOR,
                    ]);
                }

                $this->report['placed'][] = [
                    'object_id' => $id, 'name' => $name,
                    'path' => implode(' > ', array_column($path, 1)),
                    'source' => $source,
                ];
            }

            return $this->report;
        };

        // One transaction: a migration that stopped half way would leave some
        // boxes in the tree and some not, with nothing to say which.
        return $apply ? DB::connection()->transaction($work) : $work();
    }

    /**
     * The location at the end of a path, creating what is missing on the way.
     *
     * A location is the same one when it has the same parent, the same type and
     * the same name, compared without regard to case or surrounding space -
     * "Room 12" typed two ways on two boxes is one room.
     *
     * @param array      $path  [[type, name], ...] outermost first
     * @param null|array $extra description and capacity for the last location, when it is created
     */
    protected function resolve(array $path, ?array $extra = null): int
    {
        $parentId = null;
        $trail = [];
        $last = count($path) - 1;

        foreach ($path as $index => [$type, $name]) {
            $trail[] = $name;
            $key = ($parentId ?? 0).'|'.$type.'|'.mb_strtolower($name);

            if (!isset($this->known[$key])) {
                $existing = null;

                // A planned parent has no row yet, so nothing can exist under it.
                if (null === $parentId || $parentId > 0) {
                    $query = DB::table('ahg_storage_location')
                        ->where('location_type', $type)
                        ->whereRaw('LOWER(TRIM(name)) = ?', [mb_strtolower($name)]);

                    null === $parentId ? $query->whereNull('parent_id') : $query->where('parent_id', $parentId);
                    $existing = $query->orderBy('id')->first();
                }

                $label = implode(' > ', $trail).' ('.$type.')';

                if ($existing) {
                    $this->known[$key] = (int) $existing->id;
                    $this->report['locations_reused'][] = $label;
                } elseif ($this->apply) {
                    $data = ['name' => $name, 'location_type' => $type, 'parent_id' => $parentId];

                    if ($index === $last && $extra) {
                        $data += array_intersect_key($extra, array_flip(['description', 'notes', 'capacity_value', 'capacity_unit']));
                    }

                    $this->known[$key] = (int) $this->locations->createLocation($data)['id'];
                    $this->report['locations_created'][] = $label;
                } else {
                    $this->known[$key] = --$this->planned;
                    $this->report['locations_created'][] = $label;
                }
            }

            $parentId = $this->known[$key];
        }

        return (int) $parentId;
    }

    /** Every physical object with whatever the flat fields say about it. */
    protected function objects(): array
    {
        $query = DB::table('physical_object as p')
            ->leftJoin('physical_object_i18n as i', function ($join) {
                $join->on('i.id', '=', 'p.id')->where('i.culture', '=', $this->culture);
            })
            ->orderBy('p.id')
            ->select('p.id', 'i.name', 'i.location');

        if (DB::schema()->hasTable('physical_object_extended')) {
            $query->leftJoin('physical_object_extended as e', 'e.physical_object_id', '=', 'p.id');

            foreach (self::LEVELS as $level) {
                $query->addSelect('e.'.$level);
            }
        }

        return array_map(static function ($row) { return (array) $row; }, $query->get()->all());
    }

    /**
     * @return array rooms: each strongroom as a location to be; of: object id => strongroom name
     */
    protected function strongrooms(): array
    {
        if (!DB::schema()->hasTable('ahg_strongroom')) {
            return ['rooms' => [], 'of' => []];
        }

        $rooms = [];
        $names = [];

        foreach (DB::table('ahg_strongroom')->orderBy('id')->get()->all() as $room) {
            $name = trim((string) $room->name);

            if ('' === $name) {
                continue;
            }

            $names[(int) $room->id] = $name;
            $rooms[] = [
                'name' => $name,
                'description' => $room->location_description,
                'notes' => $room->notes,
                'capacity_value' => $room->capacity_value,
                'capacity_unit' => $room->capacity_unit,
            ];
        }

        $of = [];

        if (DB::schema()->hasTable('ahg_physical_object_storage')) {
            foreach (DB::table('ahg_physical_object_storage')->get()->all() as $row) {
                if (isset($names[(int) $row->strongroom_id])) {
                    $of[(int) $row->physical_object_id] = $names[(int) $row->strongroom_id];
                }
            }
        }

        return ['rooms' => $rooms, 'of' => $of];
    }
}
