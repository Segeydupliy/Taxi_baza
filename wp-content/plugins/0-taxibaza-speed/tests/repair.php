<?php
// phpcs:ignoreFile
namespace { define('ABSPATH',1); define('ARRAY_A','ARRAY_A'); define('DAY_IN_SECONDS',86400);
$GLOBALS['opt']=[]; $GLOBALS['tr_written']=[]; $GLOBALS['upd']=[];
function add_action(){} function get_option($k,$d=null){ return $GLOBALS['opt'][$k]??$d; } function update_option($k,$v,$a=null){ $GLOBALS['opt'][$k]=$v; } function delete_option($k){} function wp_cache_flush(){} function apply_filters($h,$v){return $v;}
function icl_add_string_translation($id,$l,$v,$s){ $GLOBALS['tr_written'][]=[$id,$l,$v,$s]; }
class FakeDB { public $prefix='wp_'; public $strings=[]; public $tr=[];
 function prepare($q,...$a){ return $q; }
 function get_results($q,$o=null){ return str_contains($q,'icl_string_translations') ? $this->tr : $this->strings; }
 function update($t,$d,$w){ $GLOBALS['upd'][]=[$w['id'],$d]; } }
$wpdb=new FakeDB;
// 1) uk-source, SK WPML translation curated -> keep it, write ACF SK
$wpdb->strings[]=['id'=>1,'name'=>'tb_cab_settings_notif_title','language'=>'uk','value'=>'Сповіщення','status'=>1];
$wpdb->tr[]=['string_id'=>1,'language'=>'sk','value'=>'Upozornenia','status'=>10];
$GLOBALS['opt']['options_tb_cab_settings_notif_title']='Oznámenia'; $GLOBALS['opt']['options_uk_tb_cab_settings_notif_title']='Сповіщення';
// 2) explicit content fix
$wpdb->strings[]=['id'=>2,'name'=>'tb_cat_filter_city','language'=>'uk','value'=>'Місто','status'=>1];
$wpdb->tr[]=['string_id'=>2,'language'=>'sk','value'=>'City','status'=>10];
$GLOBALS['opt']['options_tb_cat_filter_city']='Місто';
// 3) sk-source, ping-pong value; nothing else to change
$wpdb->strings[]=['id'=>3,'name'=>'tb_logout_label','language'=>'sk','value'=>'Log out','status'=>1];
$wpdb->tr[]=['string_id'=>3,'language'=>'en','value'=>'Log out','status'=>10];
$GLOBALS['opt']['options_tb_logout_label']='Odhlásiť sa';
// 4) uk-source, UK translation stale vs ACF UK -> UK translation := ACF UK (what UK visitors saw)
$wpdb->strings[]=['id'=>4,'name'=>'tb_register_label','language'=>'uk','value'=>'Зареєструватись','status'=>1];
$wpdb->tr[]=['string_id'=>4,'language'=>'uk','value'=>'Зареєструватися','status'=>10];
$GLOBALS['opt']['options_tb_register_label']='Registrovať sa'; $GLOBALS['opt']['options_uk_tb_register_label']='Зареєструватись';
require __DIR__.'/../includes/class-wpml-guard.php'; require __DIR__.'/../includes/class-wpml-repair.php';
$R='TaxiBaza\Speed\WPML_Repair'; $plan=$R::plan(); $by=[]; foreach($plan['rows'] as $r) $by[$r['id']]=$r;
$n=0;$f=0; $ck=function($t,$c)use(&$n,&$f){$n++; echo ($c?"  ok  ":"FAIL  ").$t."\n"; if(!$c)$f++;};
$ck('curated SK kept as new source and written to ACF SK', 'Upozornenia'===$by[1]['sk'] && 'Upozornenia'===$by[1]['acf_sk'] && $by[1]['set_lang']);
$ck('uk-source: UK keeps what it showed (ACF UK), written as translation', ($by[1]['tr']['uk']['to']??null)==='Сповіщення');
$ck('Slovak translation row aligned with the new source', ($by[2]['tr']['sk']['to']??null)==='Mesto' && !isset($by[1]['tr']['sk']));
$ck('content fix applied to SK and all languages', 'Mesto'===$by[2]['sk'] && 'Mesto'===$by[2]['acf_sk'] && 'City'===$by[2]['tr']['en']['to'] && 'Город'===$by[2]['tr']['ru']['to']);
$ck('ping-pong source text reset to Slovak, nothing else', 'Odhlásiť sa'===$by[3]['sk'] && $by[3]['set_value'] && !$by[3]['set_lang'] && []===$by[3]['tr'] && null===$by[3]['acf_sk']);
$ck('stale UK translation replaced by what UK visitors saw', 'Зареєструватись'===$by[4]['tr']['uk']['to'] && 'Registrovať sa'===$by[4]['sk'] && null===$by[4]['acf_sk']);
$R::apply($plan);
$ck('apply: ACF SK options written', 'Mesto'===$GLOBALS['opt']['options_tb_cat_filter_city'] && 'Upozornenia'===$GLOBALS['opt']['options_tb_cab_settings_notif_title']);
$ck('apply: source language + value updated', in_array([2,['language'=>'sk','value'=>'Mesto']],$GLOBALS['upd'],true));
$ck('apply: translations written as complete', in_array([2,'en','City',10],$GLOBALS['tr_written'],true));
echo "\n$n checks, $f failed\n"; }
