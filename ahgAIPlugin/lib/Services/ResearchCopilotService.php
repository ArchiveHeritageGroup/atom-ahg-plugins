<?php

use Illuminate\Database\Capsule\Manager as DB;

require_once dirname(__FILE__).'/CollectionChatbotService.php';

/**
 * ResearchCopilotService (#149 strand) — persistent research sessions on top of
 * the collection RAG assistant (#121).
 *
 * Turns the ephemeral, client-side-only chatbot into a researcher's saved,
 * resumable workspace: sessions + messages are persisted per user, with
 * Markdown export. The actual grounded Q&A is delegated to CollectionChatbotService.
 *
 * Copyright (C) 2026 Johan Pieterse / Plain Sailing Information Systems
 * Licensed under the GNU Affero General Public License v3.0 or later.
 */
class ResearchCopilotService
{
    private const HISTORY_TURNS = 6;

    public function listSessions(int $userId): array
    {
        return DB::table('ahg_research_session')
            ->where('user_id', $userId)
            ->orderByDesc('updated_at')
            ->limit(100)
            ->get(['id', 'title', 'updated_at'])->all();
    }

    public function createSession(int $userId, string $culture = 'en', string $title = ''): int
    {
        $now = date('Y-m-d H:i:s');
        return (int) DB::table('ahg_research_session')->insertGetId([
            'user_id' => $userId,
            'title' => $title !== '' ? mb_substr($title, 0, 255) : 'New research session',
            'culture' => $culture,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /** Owner-checked fetch; returns null if the session isn't the user's. */
    public function getSession(int $sessionId, int $userId)
    {
        return DB::table('ahg_research_session')
            ->where('id', $sessionId)->where('user_id', $userId)->first();
    }

    public function getMessages(int $sessionId, int $userId): array
    {
        if (!$this->getSession($sessionId, $userId)) {
            return [];
        }
        $rows = DB::table('ahg_research_message')
            ->where('session_id', $sessionId)->orderBy('id')->get();
        $out = [];
        foreach ($rows as $r) {
            $out[] = [
                'role' => $r->role,
                'content' => $r->content,
                'sources' => $r->sources_json ? (json_decode($r->sources_json, true) ?: []) : [],
            ];
        }
        return $out;
    }

    /**
     * Ask a question within a session: loads recent history, runs the RAG
     * assistant, persists both turns, and updates the session.
     */
    public function ask(int $sessionId, int $userId, string $message, string $culture = 'en'): array
    {
        $message = trim($message);
        if ($message === '') {
            return ['error' => 'empty_message'];
        }
        $session = $this->getSession($sessionId, $userId);
        if (!$session) {
            return ['error' => 'not_found'];
        }

        // Build history from the stored messages (last N turns).
        $history = [];
        foreach ($this->getMessages($sessionId, $userId) as $m) {
            $history[] = ['role' => $m['role'], 'content' => $m['content']];
        }
        $history = array_slice($history, -1 * (self::HISTORY_TURNS * 2));

        $result = \CollectionChatbotService::chat($message, $history, $culture);
        $answer = (string) ($result['answer'] ?? '');
        $sources = $result['sources'] ?? [];

        $now = date('Y-m-d H:i:s');
        DB::table('ahg_research_message')->insert([
            'session_id' => $sessionId, 'role' => 'user', 'content' => $message,
            'sources_json' => null, 'created_at' => $now,
        ]);
        DB::table('ahg_research_message')->insert([
            'session_id' => $sessionId, 'role' => 'assistant', 'content' => $answer,
            'sources_json' => json_encode($sources, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'created_at' => $now,
        ]);

        // Title an untitled session from its first question.
        $update = ['updated_at' => $now];
        if (in_array($session->title, ['New research session', ''], true)) {
            $update['title'] = mb_substr($message, 0, 80);
        }
        DB::table('ahg_research_session')->where('id', $sessionId)->update($update);

        return [
            'answer' => $answer,
            'sources' => $sources,
            'mode' => $result['mode'] ?? null,
            'error' => $result['error'] ?? null,
            'session_id' => $sessionId,
            'title' => $update['title'] ?? $session->title,
        ];
    }

    public function rename(int $sessionId, int $userId, string $title): bool
    {
        if (!$this->getSession($sessionId, $userId)) {
            return false;
        }
        $title = trim($title);
        if ($title === '') {
            return false;
        }
        DB::table('ahg_research_session')->where('id', $sessionId)
            ->update(['title' => mb_substr($title, 0, 255), 'updated_at' => date('Y-m-d H:i:s')]);
        return true;
    }

    public function deleteSession(int $sessionId, int $userId): bool
    {
        if (!$this->getSession($sessionId, $userId)) {
            return false;
        }
        DB::table('ahg_research_message')->where('session_id', $sessionId)->delete();
        DB::table('ahg_research_session')->where('id', $sessionId)->delete();
        return true;
    }

    /** Render a session as a Markdown transcript (with cited sources). */
    public function exportMarkdown(int $sessionId, int $userId): ?string
    {
        $session = $this->getSession($sessionId, $userId);
        if (!$session) {
            return null;
        }
        $lines = ['# '.$session->title, '', '_Research session — '.$session->updated_at.'_', ''];
        foreach ($this->getMessages($sessionId, $userId) as $m) {
            if ($m['role'] === 'user') {
                $lines[] = '## Q: '.$m['content'];
            } else {
                $lines[] = $m['content'];
                if (!empty($m['sources'])) {
                    $lines[] = '';
                    $lines[] = '**Sources:**';
                    foreach ($m['sources'] as $s) {
                        $lines[] = '- '.($s['title'] ?? '?').' ('.($s['slug'] ?? '').')';
                    }
                }
            }
            $lines[] = '';
        }
        return implode("\n", $lines);
    }
}
