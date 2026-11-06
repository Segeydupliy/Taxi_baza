<?php
// phpcs:ignoreFile
namespace { define('ABSPATH',1); define('DAY_IN_SECONDS',86400);
class WP_Hook { public $callbacks=[]; }
$GLOBALS['wp_filter']=[]; $GLOBALS['opt']=[]; $GLOBALS['lang']='sk'; $GLOBALS['calls']=[];
function add_action($h,$f,$p=10,$a=1){ global $wp_filter; if(!isset($wp_filter[$h])) $wp_filter[$h]=new WP_Hook; $wp_filter[$h]->callbacks[$p][is_array($f)?implode('::',$f):spl_object_hash((object)[])]=['function'=>$f,'accepted_args'=>$a]; }
function remove_action($h,$f,$p=10){ global $wp_filter; foreach($wp_filter[$h]->callbacks[$p] as $k=>$c){ if($c['function']===$f) unset($wp_filter[$h]->callbacks[$p][$k]); } }
function has_action($h,$f){ global $wp_filter; foreach(($wp_filter[$h]->callbacks??[]) as $p=>$cs) foreach($cs as $c) if($c['function']===$f) return $p; return false; }
function do_action($h,...$a){ global $wp_filter; foreach(($wp_filter[$h]->callbacks??[]) as $cs) foreach($cs as $c) call_user_func_array($c['function'],array_slice($a,0,$c['accepted_args'])); }
function apply_filters($h,$v){ return 'wpml_current_language'===$h?$GLOBALS['lang']:$v; }
function get_option($k,$d=false){ return $GLOBALS['opt'][$k]??$d; } function update_option($k,$v,$a=null){ $GLOBALS['opt'][$k]=$v; } function delete_option($k){ unset($GLOBALS['opt'][$k]); }
function wpml_reg($c,$n,$v,$e=false,$l=null){ $GLOBALS['calls'][]=[$c,$n,$v,$l]; }
require __DIR__.'/../includes/class-wpml-guard.php';
$G='TaxiBaza\Speed\WPML_Guard'; $n=0;$f=0; $ck=function($t,$c)use(&$n,&$f){$n++; if($c) echo "  ok  $t\n"; else {$f++; echo "FAIL  $t\n";}};
add_action('wpml_register_single_string','wpml_reg',10,5);
$G::wrap(); $G::wrap();
$ck('WPML callback moved behind the guard (once)', 10===has_action('wpml_register_single_string',[$G,'register']) && false===has_action('wpml_register_single_string','wpml_reg'));
$GLOBALS['opt']['options_tb_logout_label']='Odhlásiť sa'; $GLOBALS['opt']['options_en_tb_logout_label']='Log out';
$GLOBALS['lang']='en'; do_action('wpml_register_single_string','taxi-rental-options','tb_logout_label','Log out',false,'sk');
$ck('on /en/ the Slovak value is registered, as sk', [['taxi-rental-options','tb_logout_label','Odhlásiť sa','sk']]===$GLOBALS['calls']);
$GLOBALS['lang']='sk'; do_action('wpml_register_single_string','taxi-rental-options','tb_logout_label','Odhlásiť sa',false,'sk');
$GLOBALS['lang']='uk'; do_action('wpml_register_single_string','taxi-rental-options','tb_logout_label','Вийти',false,'sk');
$ck('same value is not re-registered (any language)', 1===count($GLOBALS['calls']));
$GLOBALS['opt']['options_tb_logout_label']='Odhlásiť'; do_action('wpml_register_single_string','taxi-rental-options','tb_logout_label','Вийти',false,'sk');
$ck('changed Slovak value is registered again', 2===count($GLOBALS['calls']) && 'Odhlásiť'===$GLOBALS['calls'][1][2]);
$GLOBALS['lang']='en'; do_action('wpml_register_single_string','taxi-rental-options','tb_only_en','EN only',false,'sk');
$ck('no Slovak value + non-sk page: nothing registered', 2===count($GLOBALS['calls']));
$GLOBALS['lang']='sk'; do_action('wpml_register_single_string','taxi-rental-options','tb_only_en','Len SK stránka',false,'sk');
$ck('no Slovak option but sk page: registered as given', 'Len SK stránka'===$GLOBALS['calls'][2][2]);
do_action('wpml_register_single_string','complianz','x','Hello',false,null); do_action('wpml_register_single_string','complianz','x','Hello',false,null);
$ck('other contexts: passed through unchanged, de-duplicated', 4===count($GLOBALS['calls']) && ['complianz','x','Hello',null]===$GLOBALS['calls'][3]);
$G::save(); $ck('cache persisted in one option', isset($GLOBALS['opt']['tbs_wpml_registered']['h']['complianz|x']));
$G::reset(); do_action('wpml_register_single_string','complianz','x','Hello',false,null);
$ck('reset forces re-registration', 5===count($GLOBALS['calls']));
echo "\n$n checks, $f failed\n"; }
