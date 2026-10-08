<?php
// #212 OaiVisibility check, standalone (stubs DB and ApiVisibility). Run: php ahgAPIPlugin/testing/oai-visibility-check.php
namespace Illuminate\Database\Capsule { class Manager { static function table($t){ return new Q; } } class Q { function whereIn(){return $this;} function whereNotNull(){return $this;} function pluck(){ return ['7']; } } }
namespace {
class sfUser {} class sfEvent {} class ApiVisibility { static function hiddenIds($u){ return [99]; } }
require __DIR__.'/../lib/OaiVisibility.php';
$h='<?xml version="1.0" encoding="UTF-8"?><OAI-PMH xmlns="http://www.openarchives.org/OAI/2.0/"><responseDate>x</responseDate><request>p</request>';
$lr=$h.'<ListRecords><record><header><identifier>oai:h:c_7</identifier></header><metadata/></record><record><header><identifier>oai:h:c_8</identifier></header></record></ListRecords></OAI-PMH>';
$li=$h.'<ListIdentifiers><header><identifier>oai:h:c_7</identifier></header></ListIdentifiers></OAI-PMH>';
$gr=$h.'<GetRecord><record><header><identifier>oai:h:c_7</identifier></header></record></GetRecord></OAI-PMH>';
$gk=$h.'<GetRecord><record><header><identifier>oai:h:c_8</identifier></header></record></GetRecord></OAI-PMH>';
$ls=$h.'<ListSets><set><setSpec>oai:h:c_7</setSpec></set><set><setSpec>oai:virtual:top-level-records</setSpec></set></ListSets></OAI-PMH>';
$id=$h.'<Identify><repositoryName>r</repositoryName></Identify></OAI-PMH>';
$u=new sfUser; $ok=0; $n=0;
function t($name,$cond){ global $ok,$n; $n++; $ok+=$cond; echo ($cond?'PASS':'FAIL')." $name\n"; }
$r=OaiVisibility::filter($lr,$u); t('ListRecords drops hidden', !str_contains($r,'c_7') && str_contains($r,'c_8'));
$r=OaiVisibility::filter($li,$u); t('ListIdentifiers all hidden -> noRecordsMatch', str_contains($r,'code="noRecordsMatch"') && !str_contains($r,'c_7'));
$r=OaiVisibility::filter($gr,$u); t('GetRecord hidden -> idDoesNotExist', str_contains($r,'code="idDoesNotExist"') && !str_contains($r,'c_7'));
t('GetRecord visible unchanged', OaiVisibility::filter($gk,$u)===$gk);
$r=OaiVisibility::filter($ls,$u); t('ListSets drops hidden collection', !str_contains($r,'c_7') && str_contains($r,'top-level'));
t('Identify untouched', OaiVisibility::filter($id,$u)===$id);
$r=OaiVisibility::filter($gk,null); t('fail closed hides record', str_contains($r,'idDoesNotExist'));
t('non-OAI content untouched', OaiVisibility::filterContent(new sfEvent,'<html>x</html>')==='<html>x</html>');
echo "$ok/$n\n";
}
