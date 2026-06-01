<?php

/*
 * This file is part of the Access to Memory (AtoM) software.
 *
 * Access to Memory (AtoM) is free software: you can redistribute it and/or modify
 * it under the terms of the GNU Affero General Public License as published by
 * the Free Software Foundation, either version 3 of the License, or
 * (at your option) any later version.
 *
 * Access to Memory (AtoM) is distributed in the hope that it will be useful,
 * but WITHOUT ANY WARRANTY; without even the implied warranty of
 * MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
 * GNU General Public License for more details.
 *
 * You should have received a copy of the GNU General Public License
 * along with Access to Memory (AtoM).  If not, see <http://www.gnu.org/licenses/>.
 */

/**
 * AJAX endpoint behind the "Generate identifier" button on the description
 * edit/create form.
 *
 * Previously this returned QubitInformationObject::generateIdentifierFromMask()
 * unconditionally — the legacy global mask — so per-sector schemes never
 * applied and disabling numbering for a sector had no effect.
 *
 * It now mirrors the identifierGenerator form component's engine order so the
 * button returns the SAME identifier that auto-fills the form and that gets
 * saved:
 *   1. SectorIdentifierService masks (settings: sector_<code>_identifier_mask*)
 *      — the engine the form preview and the create() flow actually use;
 *   2. NumberingService schemes (numbering_scheme table) — honouring the
 *      per-sector auto-generate toggle (disabled → blank, no stale number);
 *   3. the legacy global mask — only when nothing else is configured.
 *
 * NOTE on namespace: the service file declares namespace AtomExtensions\Services.
 * Both AtomFramework\ and AtomExtensions\ PSR-4 map to src/, but the *class*
 * only exists under AtomExtensions — referencing \AtomFramework\Services\* throws
 * (class-not-found / re-include), so we MUST use \AtomExtensions\Services\*.
 *
 * The button's JS posts no context, so the sector is resolved server-side: an
 * explicit `sector`/`displayStandard` request param if present, else 'archive'.
 */
class InformationObjectGenerateIdentifierAction extends sfAction
{
    public function execute($request)
    {
        $this->response->setHttpHeader('Content-Type', 'application/json; charset=utf-8');

        return $this->renderText(json_encode($this->generate($request)));
    }

    /**
     * @return array{identifier:string,disabled?:bool}
     */
    private function generate($request): array
    {
        try {
            require_once sfConfig::get('sf_root_dir') . '/atom-framework/bootstrap.php';
            require_once sfConfig::get('sf_root_dir') . '/atom-framework/src/Services/SectorIdentifierService.php';
            require_once sfConfig::get('sf_root_dir') . '/atom-framework/src/Services/NumberingService.php';

            $sector = $this->resolveSector($request);

            // 1. Settings-based sector mask (the engine the form preview uses).
            //    Returns null when the sector mask is disabled / unset.
            $preview = \AtomExtensions\Services\SectorIdentifierService::previewInfo($sector);
            if (null !== $preview && '' !== (string) ($preview['next_reference'] ?? '')) {
                return ['identifier' => (string) $preview['next_reference']];
            }

            // 2. numbering_scheme table.
            $repoId = $request->getParameter('repository_id', $request->getParameter('repository'));
            $repoId = $repoId ? (int) $repoId : null;

            $context = [];
            foreach (['repo', 'fonds', 'series', 'dept', 'type', 'project'] as $token) {
                if ($value = $request->getParameter($token)) {
                    $context[$token] = $value;
                }
            }

            $service = \AtomExtensions\Services\NumberingService::getInstance();
            $info = $service->getNumberingInfo($sector, $context, $repoId);

            if (!empty($info['scheme_name'])) {
                // Scheme exists but numbering is disabled for this sector →
                // leave the field blank for manual entry (honour the toggle).
                if (empty($info['enabled']) || empty($info['auto_generate'])) {
                    return ['identifier' => '', 'disabled' => true];
                }

                // Active scheme → preview the next reference (non-consuming;
                // the sequence is reserved at save time, not on each click).
                if ('' !== (string) ($info['next_reference'] ?? '')) {
                    return ['identifier' => (string) $info['next_reference']];
                }
            }

            // 3. Legacy global mask (back-compat for instances with neither
            //    a sector mask nor a numbering scheme configured).
            return ['identifier' => (string) QubitInformationObject::generateIdentifierFromMask()];
        } catch (\Throwable $e) {
            // Never let the button break: fall back to the legacy mask.
            return ['identifier' => (string) QubitInformationObject::generateIdentifierFromMask()];
        }
    }

    private function resolveSector($request): string
    {
        if ($sector = $request->getParameter('sector')) {
            return $sector;
        }

        $displayStandard = $request->getParameter('displayStandard');
        if ($displayStandard) {
            $mapped = \AtomExtensions\Services\NumberingService::getInstance()
                ->getSectorFromDisplayStandard((int) $displayStandard);
            if ($mapped) {
                return $mapped;
            }
        }

        return 'archive';
    }
}
