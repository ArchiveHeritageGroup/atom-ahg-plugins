<?php

/**
 * ResearchOfflinePackageService — build a self-contained, downloadable offline
 * research package for a researcher.
 *
 * The researcher selects which of their own Collections / Projects / Favourites
 * folders to take offline. We resolve those to information-object ids, scope them
 * to what the researcher may see (per-user ACL deny-set + published only), and
 * write a self-contained HTML viewer (runs from file:// on any device, no login,
 * no server) that lets them add notes, sources, metadata suggestions and files,
 * then "Save for sync" a researcher-sync.json. That file is uploaded back and
 * consumed by OfflineSyncService::applyQueue().
 *
 * Built synchronously in the web request — no background job / queue (so it can
 * never hang like the ahg_queue_job export queue did).
 */

use Illuminate\Database\Capsule\Manager as DB;

class ResearchOfflinePackageService
{
    /**
     * Resolve the selected sources to ACL-scoped, published IO ids, verifying the
     * researcher owns each selected collection/project/folder.
     *
     * @param array $sources ['collection'=>int[], 'project'=>int[], 'favorites'=>int[]]
     *
     * @return int[]
     */
    public function resolveSelectedIds($researcher, int $userId, array $sources): array
    {
        $ids = [];
        foreach ($this->resolveGroups($researcher, $userId, $sources) as $g) {
            foreach ($g['ids'] as $id) {
                $ids[$id] = true;
            }
        }

        return $this->filterAllowed(array_keys($ids), $userId);
    }

    /**
     * Resolve each selected source to its own group with member IO ids
     * (ownership-checked, pre-ACL). Used to group records by source in the viewer.
     *
     * @return array<int,array{heading:string,name:string,ids:int[]}>
     */
    public function resolveGroups($researcher, int $userId, array $sources): array
    {
        $groups = [];

        foreach (array_map('intval', $sources['collection'] ?? []) as $cid) {
            $coll = DB::table('research_collection')->where('id', $cid)
                ->where('researcher_id', $researcher->id)->first();
            if (!$coll) {
                continue;
            }
            $ids = DB::table('research_collection_item')->where('collection_id', $cid)
                ->pluck('object_id')->map(fn ($v) => (int) $v)->filter()->unique()->values()->all();
            $groups[] = ['heading' => 'Collections', 'name' => (string) $coll->name, 'ids' => $ids];
        }

        foreach (array_map('intval', $sources['project'] ?? []) as $pid) {
            $proj = DB::table('research_project')->where('id', $pid)
                ->where('owner_id', $researcher->id)->first();
            if (!$proj) {
                continue;
            }
            // Project records come from research_project_resource AND research_clipboard_project.
            $set = [];
            foreach (DB::table('research_project_resource')->where('project_id', $pid)
                ->whereIn('resource_type', ['object', 'archive_record'])->get() as $x) {
                $o = (int) ($x->object_id ?: $x->resource_id);
                if ($o) {
                    $set[$o] = true;
                }
            }
            foreach (DB::table('research_clipboard_project')->where('project_id', $pid)
                ->pluck('object_id') as $o) {
                if ($o) {
                    $set[(int) $o] = true;
                }
            }
            $groups[] = ['heading' => 'Projects', 'name' => (string) $proj->title, 'ids' => array_keys($set)];
        }

        foreach (array_map('intval', $sources['favorites'] ?? []) as $fid) {
            $folder = DB::table('favorites_folder')->where('id', $fid)
                ->where('user_id', $userId)->first();
            if (!$folder) {
                continue;
            }
            $ids = DB::table('favorites')->where('folder_id', $fid)->where('user_id', $userId)
                ->where('object_type', 'information_object')
                ->pluck('archival_description_id')->map(fn ($v) => (int) $v)->filter()->unique()->values()->all();
            $groups[] = ['heading' => 'Favourites folders', 'name' => (string) $folder->name, 'ids' => $ids];
        }

        // Individually picked records (from the search box). No ownership concept —
        // the ACL filter in filterAllowed() keeps them to records the user may see.
        $recIds = array_values(array_unique(array_filter(array_map('intval', $sources['records'] ?? []))));
        if ($recIds) {
            $groups[] = ['heading' => 'Search', 'name' => 'Records you added by search', 'ids' => $recIds];
        }

        return $groups;
    }

    /** Apply the per-user ACL deny-set (fail closed) + published-only filter. */
    private function filterAllowed(array $ids, int $userId): array
    {
        $ids = array_values(array_filter(array_unique(array_map('intval', $ids))));
        if (empty($ids)) {
            return [];
        }
        try {
            $restricted = array_flip(array_map('intval',
                \AtomExtensions\Services\Search\SearchAccessFilterService::getInstance()
                    ->getRestrictedObjectIds($userId)));
            $ids = array_values(array_filter($ids, fn ($id) => !isset($restricted[$id])));
        } catch (\Throwable $e) {
            return [];
        }
        if (empty($ids)) {
            return [];
        }
        $published = array_flip(array_map('intval', DB::table('status')
            ->whereIn('object_id', $ids)->where('type_id', 158)->where('status_id', 160)
            ->pluck('object_id')->all()));

        return array_values(array_filter($ids, fn ($id) => isset($published[$id])));
    }

    /**
     * Build record data (+ optional researcher notes) for the given ids.
     *
     * @return array<int,array>
     */
    public function buildRecords(array $ids, string $culture, $researcher, bool $includeNotes): array
    {
        if (empty($ids)) {
            return [];
        }

        $rows = DB::table('information_object as io')
            ->leftJoin('information_object_i18n as i18n', function ($j) use ($culture) {
                $j->on('io.id', '=', 'i18n.id')->where('i18n.culture', '=', $culture);
            })
            ->leftJoin('slug', 'slug.object_id', '=', 'io.id')
            ->whereIn('io.id', $ids)
            ->select('io.id', 'io.identifier', 'slug.slug', 'i18n.title',
                'i18n.scope_and_content', 'i18n.extent_and_medium',
                'i18n.archival_history', 'i18n.acquisition', 'i18n.access_conditions')
            ->get();

        $notesByObj = [];
        if ($includeNotes) {
            $notesByObj = DB::table('research_annotation')
                ->where('researcher_id', $researcher->id)
                ->whereIn('object_id', $ids)
                ->select('object_id', 'annotation_type', 'title', 'content')
                ->get()->groupBy('object_id');
        }

        $records = [];
        foreach ($rows as $r) {
            $recNotes = [];
            foreach ($notesByObj[$r->id] ?? [] as $n) {
                $recNotes[] = ['type' => $n->annotation_type, 'title' => $n->title, 'content' => $n->content];
            }
            $records[] = [
                'id' => (int) $r->id,
                'slug' => $r->slug,
                'title' => $r->title,
                'identifier' => $r->identifier,
                'scope_and_content' => $r->scope_and_content,
                'extent_and_medium' => $r->extent_and_medium,
                'archival_history' => $r->archival_history,
                'acquisition' => $r->acquisition,
                'access_conditions' => $r->access_conditions,
                'notes' => $recNotes,
            ];
        }

        return $records;
    }

    /**
     * Build the package into a temp dir and return the ZIP path.
     */
    public function build($researcher, int $userId, array $sources, bool $includeNotes, string $culture = 'en'): array
    {
        // Resolve per-source groups, then the allowed (ACL + published) id set.
        $groups = $this->resolveGroups($researcher, $userId, $sources);
        $allRaw = [];
        foreach ($groups as $g) {
            foreach ($g['ids'] as $id) {
                $allRaw[$id] = true;
            }
        }
        $allowed = array_flip($this->filterAllowed(array_keys($allRaw), $userId));

        // Filter each group down to allowed records; drop empty groups. This is
        // what the viewer uses to group records by their source in the left list.
        $outGroups = [];
        foreach ($groups as $g) {
            $gi = array_values(array_filter($g['ids'], fn ($id) => isset($allowed[$id])));
            if ($gi) {
                $outGroups[] = ['heading' => $g['heading'], 'name' => $g['name'], 'ids' => array_map('intval', $gi)];
            }
        }

        $ids = array_keys($allowed);
        $records = $this->buildRecords($ids, $culture, $researcher, $includeNotes);

        $syncToken = bin2hex(random_bytes(16));
        $researcherName = trim(($researcher->first_name ?? '') . ' ' . ($researcher->last_name ?? '')) ?: 'Researcher';
        $pkg = [
            'sync_token' => $syncToken,
            'researcher' => $researcherName,
            'generated_at' => date('c'),
            'count' => count($records),
        ];

        $tmp = sys_get_temp_dir() . '/research-offline-' . $userId . '-' . substr($syncToken, 0, 8);
        @mkdir($tmp . '/objects', 0775, true);

        // Copy thumbnails into the package (objects/) and set each record's
        // relative 'thumbnail' path, so images show offline.
        $thumbRel = $this->attachThumbnails($records, count($records) ? array_column($records, 'id') : [], $tmp);

        file_put_contents($tmp . '/data.js',
            'window.PKG=' . json_encode($pkg, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';' . "\n" .
            'window.GROUPS=' . json_encode($outGroups, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';' . "\n" .
            'window.RECORDS=' . json_encode($records, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) . ';' . "\n");
        file_put_contents($tmp . '/index.html', $this->viewerHtml($researcherName, count($records)));
        file_put_contents($tmp . '/README.txt', $this->readme($researcherName, count($records)));

        $zipPath = $tmp . '.zip';
        $zip = new \ZipArchive();
        if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            throw new \RuntimeException('Could not create the offline package.');
        }
        foreach (['index.html', 'data.js', 'README.txt'] as $f) {
            $zip->addFile($tmp . '/' . $f, $f);
        }
        foreach ($thumbRel as $rel) {
            if (is_file($tmp . '/' . $rel)) {
                $zip->addFile($tmp . '/' . $rel, $rel);
            }
        }
        $zip->close();

        // Clean the working dir (keep the zip).
        foreach (['index.html', 'data.js', 'README.txt'] as $f) {
            @unlink($tmp . '/' . $f);
        }
        foreach ($thumbRel as $rel) {
            @unlink($tmp . '/' . $rel);
        }
        @rmdir($tmp . '/objects');
        @rmdir($tmp);

        return ['zip' => $zipPath, 'count' => count($records)];
    }

    /**
     * Copy each record's thumbnail into {tmpDir}/objects/ and set its relative
     * 'thumbnail' path, so images display offline. Thumb = digital_object usage
     * 142 whose parent is the master (usage 140) of the IO; file lives at
     * sf_root_dir + path + name. Returns the relative paths copied.
     *
     * @return string[]
     */
    private function attachThumbnails(array &$records, array $ids, string $tmpDir): array
    {
        foreach ($records as &$r0) {
            $r0['thumbnail'] = null;
        }
        unset($r0);
        if (empty($ids) || empty($records)) {
            return [];
        }

        $atomRoot = rtrim((string) \sfConfig::get('sf_root_dir', '/usr/share/nginx/archive'), '/');

        $masters = DB::table('digital_object')->whereIn('object_id', $ids)->where('usage_id', 140)
            ->select('id', 'object_id')->get()->keyBy('object_id');
        $masterIds = $masters->pluck('id')->all();
        $thumbs = empty($masterIds) ? collect() : DB::table('digital_object')
            ->whereIn('parent_id', $masterIds)->where('usage_id', 142)
            ->select('parent_id', 'path', 'name')->get()->keyBy('parent_id');

        $rel = [];
        foreach ($records as &$rec) {
            $oid = (int) $rec['id'];
            if (!isset($masters[$oid])) {
                continue;
            }
            $t = $thumbs[$masters[$oid]->id] ?? null;
            if (!$t || empty($t->path) || empty($t->name)) {
                continue;
            }
            $src = $atomRoot . $t->path . $t->name;
            if (!is_file($src)) {
                continue;
            }
            $ext = pathinfo($t->name, PATHINFO_EXTENSION) ?: 'jpg';
            $dest = 'objects/' . $oid . '.' . preg_replace('/[^A-Za-z0-9]/', '', $ext);
            if (@copy($src, $tmpDir . '/' . $dest)) {
                $rec['thumbnail'] = $dest;
                $rel[] = $dest;
            }
        }
        unset($rec);

        return $rel;
    }

    private function readme(string $researcher, int $count): string
    {
        $year = date('Y');

        return <<<TXT
================================================================================
 Offline Research Package — {$researcher}
================================================================================

{$count} record(s). Runs in any web browser with NO internet or login.

HOW TO USE
  1. Unzip this folder (keep all files together — a USB stick works well).
  2. Double-click  index.html . It opens in any web browser; no internet or login.
  3. The list on the left is grouped by source (your Collections / Projects /
     Favourites). Pick a record, then add Notes / Sources / Suggestions / Files
     under it. Attached files must be under 5 MB each. Everything saves in this
     browser automatically; the search box filters the list.
  4. When you are back online, click  "Save for sync"  to download a
     researcher-sync.json file. Your attached files are embedded inside it, so it
     is self-contained — you can upload it from any computer.
  5. In Heratio, go to  Research > Work Offline  and UPLOAD that file to bring
     your work back. Metadata suggestions are reviewed by a curator; your notes,
     sources and files are added to your research.

Only records you are permitted to see are in this package. Images are thumbnails.

Produced with Heratio — The Archive & Heritage Group — https://theahg.co.za
Copyright (C) {$year} The Archive & Heritage Group. Catalogue content remains the
property of its rights holders and originating repository.
================================================================================
TXT;
    }

    private function viewerHtml(string $researcher, int $count): string
    {
        $r = htmlspecialchars($researcher, ENT_QUOTES, 'UTF-8');
        $body = <<<'HTML'
<!doctype html><html lang="en"><head><meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1"><title>Offline Research</title>
<style>
*{box-sizing:border-box}body{margin:0;font-family:-apple-system,Segoe UI,Roboto,Arial,sans-serif;background:#f7f7f7;color:#222}
header{background:#234;color:#fff;padding:.9rem 1.2rem}header h1{margin:0;font-size:1.2rem}header .m{opacity:.8;font-size:.82rem}
main{display:grid;grid-template-columns:320px 1fr;min-height:calc(100vh - 6.5rem)}
.list{background:#fff;border-right:1px solid #ddd;overflow-y:auto;max-height:calc(100vh - 3.2rem)}
.list input{width:100%;padding:.45rem;border:0;border-bottom:1px solid #eee}
.list a{display:block;padding:.5rem .8rem;border-bottom:1px solid #f1f1f1;cursor:pointer;text-decoration:none;color:#222;font-size:.9rem}
.list a.on{background:#eaf2f8;border-left:3px solid #58a}.list a small{display:block;color:#888}
.ghead{padding:.4rem .8rem;background:#234;color:#fff;font-size:.7rem;text-transform:uppercase;letter-spacing:.03em;font-weight:700}
.gname{padding:.35rem .8rem;background:#eef2f6;font-weight:600;font-size:.82rem;color:#345;border-bottom:1px solid #e3e8ee}
.gc{color:#789;font-weight:400}
.list a.gi{padding-left:1.5rem}
.detail{padding:1.2rem 1.6rem;overflow-y:auto;max-height:calc(100vh - 3.2rem)}
.detail h2{margin-top:0;color:#234}dl{display:grid;grid-template-columns:150px 1fr;gap:.3rem 1rem}dt{font-weight:600;color:#456}dd{margin:0;white-space:pre-wrap}
.tabs{display:flex;gap:.3rem;flex-wrap:wrap;margin:.6rem 0}.tabs button{border:1px solid #ccd;background:#fff;padding:.25rem .6rem;border-radius:1rem;cursor:pointer;font-size:.82rem}.tabs button.on{background:#58a;color:#fff;border-color:#58a}
.pane{display:none}.pane.on{display:block}.pane input,.pane textarea{width:100%;padding:.4rem;border:1px solid #ccc;border-radius:.25rem;margin-bottom:.35rem}
.btn{padding:.35rem .8rem;background:#58a;color:#fff;border:0;border-radius:.25rem;cursor:pointer}
.entry{background:#f6f8fa;border:1px solid #e2e6ea;border-radius:.25rem;padding:.35rem .5rem;margin-bottom:.3rem;font-size:.83rem;display:flex;justify-content:space-between}.entry .x{color:#a33;cursor:pointer;font-weight:700}
.bar{position:sticky;bottom:0;background:#1f2a37;color:#fff;display:flex;align-items:center;gap:1rem;padding:.6rem 1.2rem}.bar .g{flex:1;font-size:.85rem;opacity:.9}
.bar button{padding:.45rem 1rem;border:0;border-radius:.25rem;cursor:pointer;font-weight:600}.bar .s{background:#3a8;color:#fff}.bar .c{background:#556;color:#fff}
.empty{padding:2rem;color:#888}
</style></head><body>
<header><h1>Offline Research Package</h1><div class="m" id="hdr"></div></header>
<main>
<aside class="list"><input id="q" placeholder="Search records"><div id="ios"></div></aside>
<section class="detail" id="detail"><div class="empty">Select a record on the left.</div></section>
</main>
<div class="bar"><span class="g"><b>Working offline.</b> Add notes/sources/suggestions/files, then Save for sync and upload the file in Heratio &rsaquo; Research &rsaquo; Work Offline.</span><span id="cnt">0</span><button class="c" id="clr">Clear</button><button class="s" id="sv">Save for sync</button></div>
<script src="data.js"></script>
<script>
(function(){
  var PKG=window.PKG||{},RECS=window.RECORDS||[],GROUPS=window.GROUPS||[],byId={},cur=null;
  var QK='hros:'+(PKG.sync_token||'x')+':';
  document.getElementById('hdr').textContent=RECS.length+' record(s) — '+(PKG.researcher||'');
  RECS.forEach(function(r){byId[r.id]=r;});
  function esc(s){return String(s==null?'':s).replace(/[<>&"]/g,function(c){return({'<':'&lt;','>':'&gt;','&':'&amp;','"':'&quot;'})[c];});}
  function getA(k){try{return JSON.parse(localStorage.getItem(k)||'[]');}catch(e){return[];}}
  function setA(k,v){if(v.length)localStorage.setItem(k,JSON.stringify(v));else localStorage.removeItem(k);count();}
  function kN(id){return QK+'note:'+id;}function kS(id){return QK+'src:'+id;}function kG(id){return QK+'sug:'+id;}function kF(id){return QK+'file:'+id;}
  function match(r,f){return !f||String(r.title||'').toLowerCase().indexOf(f)>-1||String(r.identifier||'').toLowerCase().indexOf(f)>-1;}
  function itemHtml(r){return '<a class="gi'+(cur==r.id?' on':'')+'" data-id="'+r.id+'">'+esc(r.title||'Untitled')+(r.identifier?'<small>'+esc(r.identifier)+'</small>':'')+'</a>';}
  function render(f){
    f=(f||'').toLowerCase();var el=document.getElementById('ios');
    if(!GROUPS.length){ // flat fallback
      var sh=RECS.filter(function(r){return match(r,f);});
      el.innerHTML=sh.map(itemHtml).join('')||'<div class="empty">No matches</div>';return;
    }
    var html='',lastHeading='';
    GROUPS.forEach(function(g){
      var recs=g.ids.map(function(id){return byId[id];}).filter(function(r){return r&&match(r,f);});
      if(f&&!recs.length)return;
      if(g.heading!==lastHeading){html+='<div class="ghead">'+esc(g.heading)+'</div>';lastHeading=g.heading;}
      html+='<div class="gname">'+esc(g.name)+' <span class="gc">('+g.ids.length+')</span></div>';
      html+=recs.map(itemHtml).join('');
    });
    el.innerHTML=html||'<div class="empty">No matches</div>';
  }
  function ents(a,lab,kind){if(!a.length)return '<div style="font-size:.8rem;color:#999">None yet.</div>';return a.map(function(e,i){return '<div class="entry"><span>'+lab(e)+'</span><span class="x" data-k="'+kind+'" data-i="'+i+'">&times;</span></div>';}).join('');}
  function show(id){var r=byId[id];if(!r){return;}cur=id;render(document.getElementById('q').value);
    var fields=[['Identifier',r.identifier],['Scope and content',r.scope_and_content],['Extent and medium',r.extent_and_medium],['Archival history',r.archival_history],['Access conditions',r.access_conditions]];
    var dl='<dl>'+fields.filter(function(p){return p[1];}).map(function(p){return '<dt>'+esc(p[0])+'</dt><dd>'+esc(p[1])+'</dd>';}).join('')+'</dl>';
    var ex=(r.notes||[]).map(function(n){return '<div class="entry"><span><em>'+esc(n.type)+'</em>: '+esc(n.content)+'</span></div>';}).join('');
    var srcs=getA(kS(id)),sugs=getA(kG(id)),files=getA(kF(id)),note=localStorage.getItem(kN(id))||'';
    document.getElementById('detail').innerHTML='<h2>'+esc(r.title||'Untitled')+'</h2>'+(r.thumbnail?'<img src="'+esc(r.thumbnail)+'" alt="" style="max-width:220px;max-height:220px;float:right;margin:0 0 .6rem .8rem;border:1px solid #ddd;border-radius:.25rem;background:#fff;padding:.2rem">':'')+dl+(ex?'<h4>Your existing notes</h4>'+ex:'')
      +'<h4>Add offline work</h4><div class="tabs"><button data-t="n" class="on">Note</button><button data-t="s">Source ('+srcs.length+')</button><button data-t="g">Suggestion ('+sugs.length+')</button><button data-t="f">File ('+files.length+')</button></div>'
      +'<div class="pane on" data-p="n"><textarea id="cn" rows="3" placeholder="Your note">'+esc(note)+'</textarea><button class="btn" id="cnb">Save note</button></div>'
      +'<div class="pane" data-p="s"><input id="st" placeholder="Title"><input id="sa" placeholder="Author"><input id="sy" placeholder="Year"><input id="su" placeholder="URL/reference"><button class="btn" id="sb">Add source</button><div id="sl">'+ents(srcs,function(e){return esc((e.title||'')+(e.author?' — '+e.author:''));},'s')+'</div></div>'
      +'<div class="pane" data-p="g"><input id="gf" placeholder="Field (e.g. Title, Dates)"><textarea id="gt" rows="2" placeholder="Suggested correction/addition"></textarea><button class="btn" id="gb">Add suggestion</button><div style="font-size:.78rem;color:#888">Reviewed by a curator before any change.</div><div id="gl">'+ents(sugs,function(e){return esc((e.field||'')+': '+(e.text||''));},'g')+'</div></div>'
      +'<div class="pane" data-p="f"><input type="file" id="ff"><div style="font-size:.78rem;color:#888">Max 5 MB; embedded in your sync file.</div><div id="fl">'+ents(files,function(e){return esc(e.name+' ('+Math.round((e.size||0)/1024)+' KB)');},'f')+'</div></div>';
    wire(r);}
  function wire(r){var d=document.getElementById('detail');var id=r.id;
    d.querySelectorAll('.tabs button').forEach(function(b){b.addEventListener('click',function(){d.querySelectorAll('.tabs button').forEach(function(x){x.classList.remove('on');});d.querySelectorAll('.pane').forEach(function(x){x.classList.remove('on');});b.classList.add('on');var p=d.querySelector('.pane[data-p="'+b.dataset.t+'"]');if(p)p.classList.add('on');});});
    d.querySelector('#cnb').addEventListener('click',function(){var v=d.querySelector('#cn').value;if(v.trim())localStorage.setItem(kN(id),v);else localStorage.removeItem(kN(id));count();this.textContent='Saved';var t=this;setTimeout(function(){t.textContent='Save note';},1200);});
    d.querySelector('#sb').addEventListener('click',function(){var t=d.querySelector('#st').value.trim();if(!t)return;var a=getA(kS(id));a.push({title:t,author:d.querySelector('#sa').value.trim(),year:d.querySelector('#sy').value.trim(),url:d.querySelector('#su').value.trim()});setA(kS(id),a);show(id);});
    d.querySelector('#gb').addEventListener('click',function(){var f=d.querySelector('#gf').value.trim(),t=d.querySelector('#gt').value.trim();if(!f||!t)return;var a=getA(kG(id));a.push({field:f,text:t});setA(kG(id),a);show(id);});
    d.querySelector('#ff').addEventListener('change',function(){var file=this.files&&this.files[0];if(!file)return;if(file.size>5*1024*1024){alert('Max 5 MB');this.value='';return;}var rd=new FileReader();rd.onload=function(){var a=getA(kF(id));a.push({name:file.name,type:file.type,size:file.size,data:rd.result});setA(kF(id),a);show(id);};rd.readAsDataURL(file);});
    d.querySelectorAll('.entry .x').forEach(function(x){x.addEventListener('click',function(){var k=x.dataset.k,i=+x.dataset.i;var key=k=='s'?kS(id):(k=='g'?kG(id):kF(id));var a=getA(key);a.splice(i,1);setA(key,a);show(id);});});}
  function collect(){var q=[];for(var i=0;i<localStorage.length;i++){var k=localStorage.key(i);if(k.indexOf(QK)!==0)continue;var p=k.substring(QK.length).split(':');var kind=p[0],oid=+p[1];
    if(kind=='note'){var t=localStorage.getItem(k)||'';if(t.trim())q.push({kind:'annotation',object_id:oid,content:t,annotation_type:'note'});}
    else if(kind=='src'){getA(k).forEach(function(s){q.push({kind:'source',object_id:oid,title:s.title,author:s.author,year:s.year,url:s.url});});}
    else if(kind=='sug'){getA(k).forEach(function(s){q.push({kind:'metadata_suggestion',object_id:oid,field:s.field,suggestion:s.text});});}
    else if(kind=='file'){getA(k).forEach(function(f){q.push({kind:'file',object_id:oid,name:f.name,type:f.type,size:f.size,data:f.data});});}}
    return q;}
  function count(){document.getElementById('cnt').textContent=collect().length;}
  document.getElementById('ios').addEventListener('click',function(e){var a=e.target.closest('a[data-id]');if(a)show(+a.dataset.id);});
  document.getElementById('q').addEventListener('input',function(){render(this.value);});
  document.getElementById('sv').addEventListener('click',function(){var q=collect();if(!q.length){alert('Nothing to save yet.');return;}
    var payload={research_offline_sync:1,sync_token:PKG.sync_token,researcher:PKG.researcher,generated_at:new Date().toISOString(),queue:q};
    var b=new Blob([JSON.stringify(payload,null,2)],{type:'application/json'});var a=document.createElement('a');a.href=URL.createObjectURL(b);a.download='researcher-sync.json';document.body.appendChild(a);a.click();document.body.removeChild(a);});
  document.getElementById('clr').addEventListener('click',function(){if(!confirm('Clear all your offline work in this package?'))return;var ks=[];for(var i=0;i<localStorage.length;i++){var k=localStorage.key(i);if(k.indexOf(QK)===0)ks.push(k);}ks.forEach(function(k){localStorage.removeItem(k);});count();if(cur)show(cur);});
  render('');count();
})();
</script></body></html>
HTML;

        return $body;
    }
}
