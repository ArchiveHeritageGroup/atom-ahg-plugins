<?php

use Illuminate\Database\Capsule\Manager as DB;

/**
 * Keeps OAI-PMH in step with the web view (#212). Base arOaiPlugin checks only
 * publication status and the base read ACL, so descriptions hidden everywhere
 * else (classification, donor restriction, embargo, ICIP, ODRL) could still be
 * harvested through ListRecords, ListIdentifiers and GetRecord.
 *
 * Base is locked, so this filters the rendered OAI-PMH XML on
 * response.filter_content, after the action has signed in any OAI API key
 * holder. The hidden set is the same one the REST API uses (ApiVisibility).
 *
 * Fails closed: if the rule cannot be evaluated, no record is served.
 *
 * ponytail: post-filtering leaves a page shorter than the base page size and
 * resumption tokens unchanged. Harvesters follow the token regardless; the
 * upgrade path is an AHG OAI module that applies the filter in the query.
 */
class OaiVisibility
{
    private const NS = 'http://www.openarchives.org/OAI/2.0/';

    private const ERRORS = [
        'idDoesNotExist' => 'The value of the identifier argument is unknown or illegal in this repository.',
        'noRecordsMatch' => 'The combination of the values of the from, until, set and metadataPrefix arguments results in an empty list.',
    ];

    public static function filterContent(\sfEvent $event, $content)
    {
        if (!is_string($content) || false === strpos($content, '<OAI-PMH')) {
            return $content;
        }

        try {
            return self::filter($content, \sfContext::getInstance()->getUser());
        } catch (\Throwable $e) {
            error_log('oai.visibility_failed: '.$e->getMessage());

            return self::filter($content, null);
        }
    }

    /**
     * @param \sfUser|null $user null = fail closed, hide every record
     */
    public static function filter(string $xml, $user): string
    {
        $doc = new \DOMDocument();
        if (!@$doc->loadXML($xml)) {
            return $xml;
        }
        $xp = new \DOMXPath($doc);
        $xp->registerNamespace('o', self::NS);

        $verbNode = $xp->query('/o:OAI-PMH/o:GetRecord | /o:OAI-PMH/o:ListRecords | /o:OAI-PMH/o:ListIdentifiers | /o:OAI-PMH/o:ListSets')->item(0);
        if (null === $verbNode) {
            return $xml; // Identify, ListMetadataFormats, errors
        }

        $hidden = null === $user ? null : self::hiddenOaiIdentifiers($user);

        // A record is a <record> (GetRecord, ListRecords), a bare <header>
        // (ListIdentifiers) or a <set> (ListSets: collection roots).
        $items = [];
        foreach ($xp->query('o:record | o:header | o:set', $verbNode) as $node) {
            $id = $xp->evaluate('string(o:header/o:identifier | o:identifier | o:setSpec)', $node);
            $items[] = [$node, trim($id)];
        }

        $removed = 0;
        foreach ($items as [$node, $id]) {
            if (null === $hidden || isset($hidden[self::localId($id)])) {
                $node->parentNode->removeChild($node);
                ++$removed;
            }
        }
        if (0 === $removed) {
            return $xml;
        }

        $empty = count($items) === $removed;
        $hasToken = $xp->query('o:resumptionToken', $verbNode)->length > 0;
        if ('GetRecord' === $verbNode->localName) {
            self::replaceWithError($doc, $verbNode, 'idDoesNotExist');
        } elseif ($empty && !$hasToken && 'ListSets' !== $verbNode->localName) {
            self::replaceWithError($doc, $verbNode, 'noRecordsMatch');
        }

        return $doc->saveXML();
    }

    /** "oai:host:code_123" -> "123"; the trailing number is oai_local_identifier. */
    private static function localId(string $oaiIdentifier): string
    {
        return preg_match('/_(\d+)$/', $oaiIdentifier, $m) ? $m[1] : '';
    }

    /** @return array<string,true>|null hidden oai_local_identifier values; null = fail closed */
    private static function hiddenOaiIdentifiers(\sfUser $user): ?array
    {
        $ids = \ApiVisibility::hiddenIds($user);
        if (null === $ids) {
            return null;
        }
        $out = [];
        foreach (array_chunk($ids, 5000) as $chunk) {
            foreach (DB::table('information_object')->whereIn('id', $chunk)->whereNotNull('oai_local_identifier')->pluck('oai_local_identifier') as $local) {
                $out[(string) $local] = true;
            }
        }

        return $out;
    }

    private static function replaceWithError(\DOMDocument $doc, \DOMElement $verbNode, string $code): void
    {
        $error = $doc->createElementNS(self::NS, 'error', self::ERRORS[$code]);
        $error->setAttribute('code', $code);
        $verbNode->parentNode->replaceChild($error, $verbNode);
    }
}
