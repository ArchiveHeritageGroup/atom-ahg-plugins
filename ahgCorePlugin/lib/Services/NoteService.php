<?php

namespace AhgCore\Services;

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Note Service
 *
 * Manages the `note` and `note_i18n` tables.
 * Notes are attached to objects and categorized by type_id.
 */
class NoteService
{
    /**
     * Get all notes for an object, optionally filtered by type.
     */
    public static function getByObjectId(int $objectId, ?int $typeId = null, string $culture = 'en'): array
    {
        $query = DB::table('note')
            ->leftJoin('note_i18n', function ($j) use ($culture) {
                $j->on('note.id', '=', 'note_i18n.id')
                    ->where('note_i18n.culture', '=', $culture);
            })
            ->where('note.object_id', $objectId);

        if ($typeId !== null) {
            $query->where('note.type_id', $typeId);
        }

        return $query->select('note.*', 'note_i18n.content')
            ->get()
            ->all();
    }

    /**
     * Save a note.
     *
     * @return int The note ID
     */
    public static function save(int $objectId, int $typeId, string $content, string $culture, ?int $userId = null, ?int $id = null): int
    {
        if ($id) {
            // Update existing note
            DB::table('note')
                ->where('id', $id)
                ->update([
                    'type_id' => $typeId,
                    'user_id' => $userId,
                ]);
        } else {
            // Create new note
            $id = DB::table('note')->insertGetId([
                'object_id' => $objectId,
                'type_id' => $typeId,
                'user_id' => $userId,
                'source_culture' => $culture,
                'serial_number' => 0,
            ]);
        }

        // Save i18n content
        I18nService::save('note_i18n', $id, $culture, [
            'content' => $content,
        ]);

        return $id;
    }

    /**
     * Delete a note and its i18n records.
     */
    public static function delete(int $id): void
    {
        I18nService::delete('note_i18n', $id);
        DB::table('note')->where('id', $id)->delete();
    }

    /**
     * Delete all notes for an object.
     */
    public static function deleteByObjectId(int $objectId): void
    {
        $ids = DB::table('note')
            ->where('object_id', $objectId)
            ->pluck('id')
            ->all();

        foreach ($ids as $id) {
            self::delete($id);
        }
    }
}
