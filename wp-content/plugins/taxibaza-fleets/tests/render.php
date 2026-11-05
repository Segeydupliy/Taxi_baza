<?php
// phpcs:ignoreFile
// Renders templates/cabinet-fleet.php with WP stubs. Usage: php tests/render.php <mode: form|view> <lang: sk|uk>
namespace {
	define( 'ABSPATH', __DIR__ . '/' ); define( 'MINUTE_IN_SECONDS', 60 ); define( 'MB_IN_BYTES', 1048576 );
	$mode = $argv[1] ?? 'form'; $lang = $argv[2] ?? 'uk';
	$GLOBALS['__hooks']=[]; $GLOBALS['__meta']=[]; $GLOBALS['__users']=[ 7 => [ 'fleet' ] ]; $GLOBALS['__options']=[];
	function add_action($h,$cb,$p=10,$a=1){ $GLOBALS['__hooks'][$h][$p][]=[$cb,$a]; } function add_filter($h,$cb,$p=10,$a=1){ add_action($h,$cb,$p,$a); }
	function do_action(...$a){} function apply_filters($h,$v,...$a){ if('wpml_current_language'===$h) return $GLOBALS['lang']; return $v; } function has_action($h){return false;}
	function register_activation_hook(){} function __($s){return $s;} function get_user_meta($id,$k,$s=false){return $GLOBALS['__meta'][$id][$k]??'';} function update_user_meta($id,$k,$v){$GLOBALS['__meta'][$id][$k]=$v;} function delete_user_meta($id,$k){unset($GLOBALS['__meta'][$id][$k]);}
	function get_option($k,$d=false){return $GLOBALS['__options'][$k]??$d;} function update_option($k,$v,$a=null){$GLOBALS['__options'][$k]=$v;}
	function get_users($a){return array_map('strval',array_keys($GLOBALS['__users']));} function get_userdata($id){return (object)['ID'=>$id,'roles'=>$GLOBALS['__users'][$id]??[],'display_name'=>'Gold Fleet s.r.o.','user_email'=>'goldfleet.s.r.o@info.com'];}
	function wp_get_current_user(){return get_userdata(7);} function get_current_user_id(){return 7;} function count_user_posts(){return 0;} function get_transient(){return false;} function delete_transient(){}
	function sanitize_key($s){return strtolower(preg_replace('/[^a-z0-9_\-]/i','',$s));} function wp_unslash($s){return $s;} function esc_html($s){return htmlspecialchars((string)$s,ENT_QUOTES);} function esc_attr($s){return htmlspecialchars((string)$s,ENT_QUOTES);} function esc_url($s){return htmlspecialchars((string)$s,ENT_QUOTES);} function esc_textarea($s){return htmlspecialchars((string)$s,ENT_QUOTES);}
	function selected($a,$b,$e=true){$r=((string)$a===(string)$b)?' selected="selected"':''; if($e) echo $r; return $r;} function checked($a,$b=true,$e=true){$r=($a==$b)?' checked="checked"':''; if($e) echo $r; return $r;}
	function wp_nonce_field(){echo '<input type="hidden" name="_wpnonce" value="x">';} function admin_url($p=''){return '#'.$p;} function home_url($p=''){return $p;} function add_query_arg($k,$v=null,$u=''){ if(is_array($k)){$u=$v; return $u.'?'.http_build_query($k);} return $u.'?'.$k.'='.$v; }
	function wp_get_attachment_image_url(){return '';} function is_page_template(){return true;} function sanitize_html_class($s){return $s;}
	function get_header(){ echo '<!doctype html><html lang="'.$GLOBALS['lang'].'"><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><link rel="stylesheet" href="tokens.css"><link rel="stylesheet" href="style.css"><link rel="stylesheet" href="cabinet.css"><style>body{margin:0;font-family:Inter,system-ui,sans-serif}.stub-h{height:86px;border-bottom:1px solid #e5e5e5;display:flex;align-items:center;padding:0 96px;font-weight:800;font-size:24px}</style><script>window.tbfCabinet={i18n:{required:"req",number:"num",phone:"ph",pickOne:"pick",logo:"logo",readMore:"more",collapse:"less",feeFixed:"€",feePct:"%"}}</script></head><body><div class="stub-h">TaxiBaza.sk</div>'; }
	function get_footer(){ echo '<div style="background:#000;height:200px"></div><script src="cabinet.js"></script></body></html>'; }
	$GLOBALS['lang']=$lang;
	require dirname(__DIR__).'/taxibaza-fleets.php';
	$p = [ 'name'=>'Gold Fleet s.r.o.','phone'=>'+421 902 123 456','ico'=>'54 123 456','telegram'=>'gold_fleet_s.r.o.','whatsapp'=>'gold_fleet_s.r.o.','description'=>'Надійна флотилія з чесними умовами для нових і досвідчених водіїв. Працюємо в Братиславі та Трнаві з 2019 року — допоможемо з реєстрацією на платформах, концесією і першими поїздками.','logo_id'=>0,'platforms'=>['uber','bolt','hopin'],'langs'=>['sk','uk','en'],'models'=>['own','fleet'],'own_pay_type'=>'percent','own_fee'=>'8','own_fee_base'=>'gross','rent_price'=>'210','rent_period'=>'week','rent_comm_on'=>true,'rent_comm'=>'8','rent_dep_on'=>true,'rent_dep'=>'200','rent_dep_return'=>'handover','rent_min'=>'14','rent_min_unit'=>'days','rent_ext'=>'weekly','inc'=>['insurance','service'],'pay_freq'=>'weekly','pay_day'=>'mon','pay_method'=>['bank'],'perk_fuel'=>true,'fuel_discount'=>'4','fuel_unit'=>'cents','fuel_network'=>'','fuel_for'=>['own'],'perk_service'=>true,'svc_discount'=>'15','svc_where'=>'partner','svc_what'=>['service','tires'],'svc_for'=>['own'],'perk_concession'=>true,'con_what'=>'help','con_cost'=>'free','con_for'=>['own'],'req_age'=>'21','req_exp'=>'2','docs'=>['id'] ];
	if ( 'form' === $mode ) { $GLOBALS['__meta'][7]['tbf_status']='approved'; $GLOBALS['__meta'][7]['tbf_profile_live']=$p; $_GET=['tab'=>'profile','edit'=>'1']; }
	else { $GLOBALS['__meta'][7]['tbf_status']='approved'; $GLOBALS['__meta'][7]['tbf_profile_live']=$p; $_GET=['tab'=>'profile']; }
	require dirname(__DIR__).'/templates/cabinet-fleet.php';
}
