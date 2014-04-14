<?php
// $Id: nikocale.inc.php,v 0.1.0 2007/10/15 HOMMA Takashi $
// 
// honma@daniel-soft.com
// http://
// ----

/** ����ɽ������ */
define('PLUGIN_NIKOCALE_DISPLAY_DAYS', 14);
/** ̤���ɽ������ */
define('PLUGIN_NIKOCALE_AFTER_DAYS', 2);

/** Good,Normal,Bad �˥��˥������ѥ� */
define('PLUGIN_NIKOCALE_IMAGE_GOOD', "image/niko/good32.gif");
define('PLUGIN_NIKOCALE_IMAGE_NORMAL', "image/niko/normal32.gif");
define('PLUGIN_NIKOCALE_IMAGE_BAD', "image/niko/bad32.gif");

/** ������ɽ���������ʤ��ΤޤޤΥ������ʤ� 0 �� */
define('PLUGIN_NIKOCALE_IMAGE_SIZE', 24);

/** ���������ǻ��Ѥ��륹�����륷������� */
define('PLUGIN_NIKOCALE_STYLE_NAME', "style_td_day");
define('PLUGIN_NIKOCALE_STYLE_WEEKDAY', "style_td_day");
define('PLUGIN_NIKOCALE_STYLE_SATURDAY', "style_td_sat");
define('PLUGIN_NIKOCALE_STYLE_SUNDAY', "style_td_sun");
define('PLUGIN_NIKOCALE_STYLE_TODAY', "style_td_today");

switch (LANG) {
case 'ja':
	define('PLUGIN_PCOMMENT_FORM_MEMBER_NM', '�ʤޤ�');
	define('PLUGIN_PCOMMENT_FORM_DATE_NM', '����');
	define('PLUGIN_PCOMMENT_FORM_FEELING_NM', '��ʬ');
	define('PLUGIN_PCOMMENT_FORM_SUBMIT_NM', '��Ͽ');
	define('PLUGIN_PCOMMENT_FORM_BACK_NM', '���');
	break;
default:
	define('PLUGIN_PCOMMENT_FORM_MEMBER_NM', 'Name');
	define('PLUGIN_PCOMMENT_FORM_DATE_NM', 'Date');
	define('PLUGIN_PCOMMENT_FORM_FEELING_NM', 'Feeling');
	define('PLUGIN_PCOMMENT_FORM_SUBMIT_NM', 'Submit');
	define('PLUGIN_PCOMMENT_FORM_BACK_NM', 'Back');
}

define('PLUGIN_NIKOCALE_FORM_MEMBER', "member");
define('PLUGIN_NIKOCALE_FORM_DATE', "date");
define('PLUGIN_NIKOCALE_FORM_FEELING', "feeling");
define('PLUGIN_NIKOCALE_FORM_COMMENT', "comment");
define('PLUGIN_NIKOCALE_GET_REFER', "refer");
define('PLUGIN_NIKOCALE_GET_VIEW', "view");
define('PLUGIN_NIKOCALE_GET_EDIT', "edit");

define('PLUGIN_NIKOCALE_SUFFIX', "/nikocale");

// Update recording page's timestamp instead of parent's page itself
define('PLUGIN_NIKOCALE_TIMESTAMP', 0);

function plugin_nikocale_action() {

	global $vars;

	$refer = isset($vars[ PLUGIN_NIKOCALE_GET_REFER]) ? $vars[ PLUGIN_NIKOCALE_GET_REFER] : '';

	if ( isset( $vars[ PLUGIN_NIKOCALE_GET_VIEW]) || isset( $vars[ PLUGIN_NIKOCALE_GET_EDIT])) {
		$page_bak = strip_bracket($vars['page']);
		$vars['page'] = $refer !== '' ? $refer : '*';

		$ret['msg']  = 'nikocale ' . htmlspecialchars( $vars['page'] . ( isset( $vars[ PLUGIN_NIKOCALE_GET_VIEW]) ? '/View' : '/Edit'));
		$ret['body'] = '<a href="' . get_script_uri() . '?' . rawurlencode($refer) . '">&lt;&lt;&lt; ' . PLUGIN_PCOMMENT_FORM_BACK_NM . '</a>'
			. call_user_func_array('plugin_nikocale_convert', array());

		$vars['page'] = $page_bak;

		return $ret;
	}

	$member = $vars[ PLUGIN_NIKOCALE_FORM_MEMBER];
	$date = $vars[ PLUGIN_NIKOCALE_FORM_DATE];
	$feeling = $vars[ PLUGIN_NIKOCALE_FORM_FEELING];
	$comment = $vars[ PLUGIN_NIKOCALE_FORM_COMMENT];

	if ( $member === '') {
		$vars['page'] = $refer;
		return plugin_nikocale_error_required( PLUGIN_PCOMMENT_FORM_MEMBER_NM);
	}
	if ( $date === '') {
		$vars['page'] = $refer;
		return plugin_nikocale_error_required( PLUGIN_PCOMMENT_FORM_DATE_NM);
	}
	if ( !checkdate( substr( $date, 5, 2), substr( $date, 8, 2), substr( $date, 0, 4))) {
		$vars['page'] = $refer;
		return array(
			'msg' =>'���ϥ��顼.',
			'body'=>'���դ� yyyy/mm/dd ���������Ϥ��Ƥ�������.' ,
			'collided'=>TRUE
			);
	}
	if ( $feeling === '') {
		$vars['page'] = $refer;
		return plugin_nikocale_error_required( PLUGIN_PCOMMENT_FORM_FEELING_NM);
	}
	
	$yyyymmdd = date( 'Ymd', mktime( 0, 0, 0, substr( $date, 5, 2), substr( $date, 8, 2), substr( $date, 0, 4)));
	
	$doUpdated = false;
	foreach ( plugin_nikocale_get_pages( $refer . PLUGIN_NIKOCALE_SUFFIX) as $nikocale_page) {
		$content = new PluginNikocaleContent( $nikocale_page);
		if ( $content->get_name() === $member) {
			$content->set_detail( $yyyymmdd, $feeling, $comment);
			$content->write( $nikocale_page);
			
			$doUpdated = true;
			break;
		}
	}
	if ( ! $doUpdated) {
		// ����
		$content = new PluginNikocaleNewContent( $member);
		$content->set_detail( $yyyymmdd, $feeling, $comment);
		$content->write( plugin_nikocale_get_new_page( $refer . PLUGIN_NIKOCALE_SUFFIX));
	}
	header('Location: ' . get_script_uri() . '?' . rawurlencode($refer));
	exit;
}

function plugin_nikocale_convert() {

	global $vars;

	$TIMESTAMP_OF_DAY = 60 * 60 * 24;

	$today = mktime( 0, 0, 0, get_date('m'), get_date('d'), get_date('Y')) - LOCALZONE + ZONETIME;

	$today_day = date("d", $today) + 0;
	$today_month = date("m", $today) + 0;
	$today_year = date("Y", $today) + 0;
	
	$is_view = isset( $vars[ PLUGIN_NIKOCALE_GET_VIEW]);
	$period = isset( $vars[ PLUGIN_NIKOCALE_GET_VIEW]) ? $vars[ PLUGIN_NIKOCALE_GET_VIEW] + 0 : 0;
	$start_date = mktime( 0, 0, 0, $today_month, $today_day - PLUGIN_NIKOCALE_DISPLAY_DAYS + 1 + PLUGIN_NIKOCALE_DISPLAY_DAYS * $period, $today_year);
	$end_date = mktime( 0, 0, 0, $today_month, $today_day + ( $is_view ? 0 : PLUGIN_NIKOCALE_AFTER_DAYS) + PLUGIN_NIKOCALE_DISPLAY_DAYS * $period, $today_year);

	$page = isset($vars['page']) ? $vars['page'] : '';

	$prev_date_str = date( 'Y-m-d', $start_date);
	$end_date_str = date( 'Y-m-d', $end_date);

	$params = array(
			'plugin' => 'nikocale',
			PLUGIN_NIKOCALE_GET_REFER => $page,
			PLUGIN_NIKOCALE_GET_VIEW => $period + 1
		);
	$href_next = get_script_uri() . '?' . plugin_nikocale_make_get_param( $params);
	$params[ PLUGIN_NIKOCALE_GET_VIEW] = $period - 1;
	$href_prev = get_script_uri() . '?' . plugin_nikocale_make_get_param( $params);

	$ret = '';
	$ret .= <<<EOD
<table border="0" summary="calendar frame">
 <tr><td class="style_td_caltop2">
   <a href="$href_prev">&lt;&lt;</a>
   <strong>$prev_date_str �� $end_date_str</strong>
   <a href="$href_next">&gt;&gt;</a></td></tr>
 <tr>
  <td valign="top">
   <table class="style_calendar" cellspacing="1" border="0" summary="calendar body">
EOD;

	$ret .= "\n";
/*
	$ret .= '<colgroup class="' . PLUGIN_NIKOCALE_STYLE_WEEKDAY . 'tyle_td_day" width=50 />' . "\n";
	for ( $i = PLUGIN_NIKOCALE_DISPLAY_DAYS * -1; $i <= PLUGIN_NIKOCALE_AFTER_DAYS; $i++) {
		if ( $i === 0) { // Today
			$style = PLUGIN_NIKOCALE_STYLE_TODAY;
		}
		else {
			$i_date = mktime( 0, 0, 0, $today_month, $today_day + $i, $today_year);
			switch ( date( "w", $i_date)) {
				case 0:
					$style = PLUGIN_NIKOCALE_STYLE_SUNDAY;
					break;
				case 6:
					$style = PLUGIN_NIKOCALE_STYLE_SATURDAY;
					break;
				default:
					$style = PLUGIN_NIKOCALE_STYLE_WEEKDAY;
					break;
			}
		}
		$ret .= '<colgroup class="' . $style . '" width=20 />' . "\n";
	}
*/
	$ret .= "<tr>\n";
	$ret .= '<td class="' . PLUGIN_NIKOCALE_STYLE_NAME . '"><div class ="small">�ʤޤ�</div></td>' . "\n";

	// ** header **
	$style_sheet_of_days = array();
	$yesterdayMonth = 0;
	for ( $i_timestamp = $start_date; $i_timestamp <= $end_date; $i_timestamp += $TIMESTAMP_OF_DAY) {

		$wday = date( "w", $i_timestamp) + 0;

		$i_month = date( 'm', $i_timestamp);

		if ( $i_timestamp === $today) {
			$style = PLUGIN_NIKOCALE_STYLE_TODAY;
		}
		else if ( $wday === 0) {
			$style = PLUGIN_NIKOCALE_STYLE_SUNDAY;
		}
		else if ( $wday === 6) {
			$style = PLUGIN_NIKOCALE_STYLE_SATURDAY;
		}
		else {
			$style = PLUGIN_NIKOCALE_STYLE_WEEKDAY;
		}

		$link = '<div class ="small">';
		if ( $yesterdayMonth !== $i_month) {
			$yesterdayMonth = $i_month;
			$link .= sprintf( "%d/", $i_month);
 		}
		$link .= sprintf( '<br>%d</div>', date( 'd', $i_timestamp) + 0);
		$ret .= "     <td class=\"$style\" valign=\"top\">$link</td>\n";
		
		$style_sheet_of_days[ date( 'Ymd', $i_timestamp)] = $style;
	}
	$ret .= "\n";

	$contents = array();
	// ** body **
	foreach ( plugin_nikocale_get_pages( $page . PLUGIN_NIKOCALE_SUFFIX) as $nikocale_page) {
		$content = new PluginNikocaleContent( $nikocale_page);
		$contents[ $nikocale_page] = $content;
		
		$ret .= '<tr>';
		$ret .= '<td class="' . PLUGIN_NIKOCALE_STYLE_NAME . '">'
			. '<a title ="edit" href="' . get_script_uri() . '?cmd=edit&page=' . rawurlencode( $nikocale_page) . '">'
			. '<div class ="small" align="left">' . $content->get_name() . '</div></a></td>';

		for ( $i_timestamp = $start_date; $i_timestamp <= $end_date; $i_timestamp += $TIMESTAMP_OF_DAY) {
			$yyyymmdd = date( "Ymd", $i_timestamp);
			$ret .= '<td class="' . $style_sheet_of_days[ $yyyymmdd] . '" valign="top">';
			$ret .= $content->get_detail_image_tag_with_anchor( $yyyymmdd, $page);
			$ret .= "</td>\n";
		}

		$ret .= '</tr>';
	}

	// ** footer **
	$ret .= "<tr>";
	$ret .= '<td class="' . PLUGIN_NIKOCALE_STYLE_NAME . '">'
		. '<a href="' . get_script_uri() . '?cmd=newpage&page='
		. rawurlencode( plugin_nikocale_get_new_page( $page . PLUGIN_NIKOCALE_SUFFIX)) . '">'
		. '<div class ="small">[New]</div></a></td>' . "\n";
	for ( $i_timestamp = $start_date; $i_timestamp <= $end_date; $i_timestamp += $TIMESTAMP_OF_DAY) {
		$ret .= '<td class="' . $style_sheet_of_days[ date( "Ymd", $i_timestamp)] . '" valign="top">&nbsp;</td>' . "\n";
	}
	$ret .= "</tr>\n";

	$ret .= "   </table>\n";
	$ret .= "  </td>\n";
	$ret .= " </tr>\n";
	$ret .= "</table>\n";


	if ( isset( $vars[ PLUGIN_NIKOCALE_GET_EDIT])) {
		$yyyymmdd = $vars[ PLUGIN_NIKOCALE_FORM_DATE];
		$content = (object)$contents[ $vars[ PLUGIN_NIKOCALE_GET_EDIT]];
		
		$member = $content->get_name();
		$date = substr( $yyyymmdd, 0, 4) . '/' . substr( $yyyymmdd, 4, 2) . '/' . substr( $yyyymmdd, 6, 2);
		$feeling = $content->get_detail_feeling( $yyyymmdd);
		$comment = $content->get_detail_comment( $yyyymmdd);
	}
	else {
		$yyyymmdd = '';
		$member = '';
		$date = '';
		$feeling = '';
		$comment = '';
	}

	$ret .= '<form action="' . get_script_uri() . '" method="post">' . "\n";
	$ret .= '<input type="hidden" name="plugin" value="nikocale" />';
	$ret .= '<input type="hidden" name="' . PLUGIN_NIKOCALE_GET_REFER . '" value="' . htmlspecialchars( $page) . '" />';
	$ret .= "<div>";
	$ret .= PLUGIN_PCOMMENT_FORM_MEMBER_NM . ':<input type="text" name="' . PLUGIN_NIKOCALE_FORM_MEMBER . '" size="15" value="' . $member . '" />';
	$ret .= '&nbsp;' . PLUGIN_PCOMMENT_FORM_DATE_NM . ':<input type="text" name="' . PLUGIN_NIKOCALE_FORM_DATE . '" size="13" value="'
		. ($date != '' ? $date : date('Y/m/d', $today)) . '" maxlength=10 />';
	$ret .= '&nbsp;' . PLUGIN_PCOMMENT_FORM_FEELING_NM . ':<SELECT NAME="' . PLUGIN_NIKOCALE_FORM_FEELING . '">';
	$ret .= '<OPTION VALUE="' . PluginNikocaleDetail::FEELING_GOOD . '"' . ($feeling === PluginNikocaleDetail::FEELING_GOOD ? ' SELECTED' : '') . '><TT>(^^)</TT> Good!';
	$ret .= '<OPTION VALUE="' . PluginNikocaleDetail::FEELING_NORMAL . '"' . ($feeling == '' || $feeling === PluginNikocaleDetail::FEELING_NORMAL ? ' SELECTED' : '') . '><TT>(--)</TT> Normal';
	$ret .= '<OPTION VALUE="' . PluginNikocaleDetail::FEELING_BAD . '"' . ($feeling === PluginNikocaleDetail::FEELING_BAD ? ' SELECTED' : '') . '><TT>:-)</TT> Bad...';
	$ret .= '</SELECT>';
	$ret .= '<input type="text" name="' . PLUGIN_NIKOCALE_FORM_COMMENT . '" size="60" value="' . $comment . '" />';
	$ret .= '<input type="submit" value="' . PLUGIN_PCOMMENT_FORM_SUBMIT_NM . '" />';
	$ret .= "</div>";
	$ret .= "</form>\n";

/*
	$a = "2007/1/1";
	$b = mktime( 0, 0, 0, substr( $a, 5, 2), substr( $a, 8, 2), substr( $a, 0, 4));
	$ret .= ( $b == "") ? "true" : "false";
	$ret .= ( checkdate( substr( $a, 5, 2), substr( $a, 8, 2), substr( $a, 0, 4))) ? "true" : "false";
*/
	return $ret;
}

function plugin_nikocale_get_pages( $prefix) {
	$pages = array();
	foreach ( get_existpages() as $page) {
		if ( strpos( $page, $prefix) === 0) {
			$pages[] = $page;
		}
	}
	natcasesort($pages);
	return $pages;
}

function plugin_nikocale_get_new_page( $prefix) {
	global $vars;
	
	$i = 1;
	while ( is_page( $new_page = $prefix . '_' . sprintf( "%02d", $i))) {
		$i++;
	}
	return $new_page;
}

/**
 * Ϣ��������Ȥ� GET�ѥ�᡼����key1=value1&key2=value2&...�ˤ��֤�
 * @param Ϣ������
 * @return GET�ѥ�᡼��
 */
function plugin_nikocale_make_get_param( $array) {
	$keys = array_keys( $array);
	$values = array_values( $array);
	$result = '';
	for ( $i = 0; $i < count($keys); $i++) {
		$result .= rawurlencode( $keys[ $i]) . '=' . rawurlencode( $values[ $i]) . '&';
	}
	return substr( $result, 0, strlen( $result) - 1);
}

function plugin_nikocale_error_required( $s) {
	return array(
		'msg' =>'���ϥ��顼.',
		'body'=> $s . '��̤���ϤǤ�.' ,
		'collided'=>TRUE
		);
}

class LocalException extends Exception {}
class IllegalArgumentException extends LocalException {}

/**
 * �˥�������󥯥饹
 * ( this : �˥��������ڡ��� ) = (1:1) �Ȥʤ�
 */
class PluginNikocaleContent {
	/** ���ȤΥե�����̾ */
	private $page_name;
	/** ̾�� */
	protected $name;
	/** ���� */
	private $details;	// PluginNikocaleDetail[]�Ȥ��ƻ���
	
	/**
	 * ���󥹥ȥ饯��
	 */
	public function PluginNikocaleContent( $nikocale_page) {
		$this->page_name = $nikocale_page;
		$nikocale_body = join( '', get_source( htmlspecialchars( $nikocale_page)));
		
		$rows = split( "\n", $nikocale_body);
		
		$this->name = $rows[0];

		$this->details = array();
		foreach ( $rows as $row) {
			list( $yyyymmdd, $feeling, $comment) = split( ",", $row, 3);
			
			$row_year = substr( $yyyymmdd, 0, 4);
			$row_month = substr( $yyyymmdd, 4, 2);
			$row_day = substr( $yyyymmdd, 6, 2);
			if ( !checkdate( $row_month, $row_day, $row_year)) {
				continue;
			}
			
			$this->details[ $yyyymmdd] = PluginNikocaleDetail::create( $yyyymmdd, $feeling, $comment);
		}
	}
	
	/**
	 * ���پ��󤬴ޤޤ�뤫���֤�
	 * @param $yyyymmdd	���پ���Υ���
	 * @return �ޤޤ�Ƥ����硢true
	 */
	public function contains( $yyyymmdd) {
		return isset( $this->details[ $yyyymmdd]);
	}
	/**
	 * ̾�����֤�
	 * @return ̾��
	 */
	public function get_name() {
		return $this->name;
	}
	
	/**
	 * ���پ�����֤�
	 * @param $yyyymmdd	���پ���Υ���
	 * @return ���پ���ʳ������ʤ����϶������پ����
	 */
	private function get_detail( $yyyymmdd) {
		if ( $this->contains( $yyyymmdd)) {
			return $this->details[ $yyyymmdd];
		}
		else {
			return PluginNikocaleDetail::null_object();
		}
	}
	/**
	 * ���پ���Υ��᡼���������֤�
	 * @param $yyyymmdd	���پ���Υ���
	 * @return ���᡼������
	 */
	public function get_detail_image_tag( $yyyymmdd) {
		$obj = (object)$this->get_detail( $yyyymmdd);
		return $obj->get_image_tag();
	}
	/**
	 * �Խ����󥫡��դ��Υ��᡼���������֤�
	 * @param	$refer		���ܸ��ڡ���̾
	 * @param	$page_name	�Խ��ڡ���̾
	 * @return	�Խ����󥫡��դ��Υ��᡼������
	 */
	public function get_detail_image_tag_with_anchor( $yyyymmdd, $page) {
		$obj = (object)$this->get_detail( $yyyymmdd);
		return $obj->get_image_tag_with_anchor( $page, $this->page_name);
	}
	/**
	 * ���پ���ε�ʬ���֤�
	 * @param $yyyymmdd	���پ���Υ���
	 * @return ��ʬ
	 */
	public function get_detail_feeling( $yyyymmdd) {
		$obj = (object)$this->get_detail( $yyyymmdd);
		return $obj->get_feeling();
	}
	/**
	 * ���پ���Υ����Ȥ��֤�
	 * @param $yyyymmdd	���پ���Υ���
	 * @return ������
	 */
	public function get_detail_comment( $yyyymmdd) {
		$obj = (object)$this->get_detail( $yyyymmdd);
		return $obj->get_comment();
	}
	/**
	 * ���پ�������ꤹ��
	 * @param $yyyymmdd	YYYYMMDD������ǯ�����ʥ�����
	 * @param $feeling	��ʬ
	 * @param $comment	�����ȡʾ�ά�ġ�
	 */
	public function set_detail( $yyyymmdd, $feeling, $comment = '') {
		if ( !checkdate( substr( $yyyymmdd, 4, 2), substr( $yyyymmdd, 6, 2), substr( $yyyymmdd, 0, 4))) {
			throw new IllegalArgumentException('���� $yyyymmdd ���ͤ������Ǥ�');
		}
		if ( $feeling === '') {
			throw new IllegalArgumentException('���� $feeling ��ɬ�ܤǤ�');
		}
		$this->details[ $yyyymmdd] = PluginNikocaleDetail::create( $yyyymmdd, $feeling, $comment);
	}
	/**
	 * �˥������������������ƥ����Ȥ��֤�
	 * @return �˥��������ƥ�����
	 */
	public function make_page_format() {
		$details = array();
		$details[] = $this->name;
		foreach ( $this->details as $detail) {
			$obj = (object) $detail;
			$details[] = join( ',', array( $obj->get_yyyymmdd(), $obj->get_feeling(), $obj->get_comment()));
		}
		return join( "\n", $details);
	}
	/**
	 * �˥���������ڡ����˽��Ϥ���
	 * @param $page_name	�оݥڡ���
	 */
	public function write( $page_name) {
		page_write( $page_name, $this->make_page_format(), PLUGIN_NIKOCALE_TIMESTAMP);
	}
}
/**
 * �����桼�����Ѥ�PluginNikocaleContent
 */
class PluginNikocaleNewContent extends PluginNikocaleContent {
	/**
	 * ���󥹥ȥ饯��
	 */
	public function PluginNikocaleNewContent( $new_name) {	// overload�Ȥ��ʤ����ɥ��֥��饹�ʤ鵿��Ū�˲�ǽ
		$this->name = $new_name;
	}
}

/**
 * �˥�����ܺپ��󥯥饹
 */
class PluginNikocaleDetail {
	
	/** ��ʬ: happy */
	const FEELING_GOOD		= '(^^)';
	/** ��ʬ: normal */
	const FEELING_NORMAL	= '(--)';
	/** ��ʬ: sad.. */
	const FEELING_BAD		= '(__)';
	
	/** YYYYMMDD������ǯ���� */
	private $yyyymmdd;
	/** ��ʬ */
	private $feeling;
	/** ������ */
	private $comment;
	/** ������ɽ�������� */
	private $image_size = PLUGIN_NIKOCALE_IMAGE_SIZE;
	
	/**
	 * ���󥹥ȥ饯�����������
	 * @param	$yyyymmdd	YYYYMMDD������ǯ����
	 * @param	$feeling	��ʬ
	 * @param	$comment	������
	 */
	private function PluginNikocaleDetail( $yyyymmdd, $feeling, $comment) {
	
		$this->comment = $comment;
		$this->yyyymmdd = $yyyymmdd;
		switch ( $feeling) {
			case ":)":
			case ":D":
			case "(^^)":
			case "(^-^)":
			case "happy":
			case "good":
			case PluginNikocaleDetail::FEELING_GOOD:
				$this->feeling = PluginNikocaleDetail::FEELING_GOOD;
				break;
			case "(--)":
			case "(^^;":
			case "so-so":
			case "normal":
			case PluginNikocaleDetail::FEELING_NORMAL:
				$this->feeling = PluginNikocaleDetail::FEELING_NORMAL;
				break;
			case ":-)":
			case "(--;":
			case "(__;":
			case "(__)":
			case "sad":
			case "bad":
			case PluginNikocaleDetail::FEELING_BAD:
				$this->feeling = PluginNikocaleDetail::FEELING_BAD;
				break;
			default:
				$this->feeling = PluginNikocaleDetail::FEELING_NORMAL;
				break;
		}
	}

	/**
	 * Null���֥������Ȥ��֤�
	 * @return PluginNikocaleDetail���Υ��󥹥���
	 */
	public static function null_object() {
		return PluginNikocaleNullDetail::get_instance();
	}
	/**
	 * �ե����ȥ꡼�᥽�å�
	 * @return PluginNikocaleDetail���Υ��󥹥���
	 */
	public static function create( $yyyymmdd, $feeling = '', $comment = '') {
		if ( !checkdate( substr( $yyyymmdd, 4, 2), substr( $yyyymmdd, 6, 2), substr( $yyyymmdd, 0, 4))) {
			return PluginNikocaleDetail::null_object();
		}
		else {
			return new PluginNikocaleDetail( $yyyymmdd, $feeling, $comment);
		}
	}
	/**
	 * ���᡼���������֤�
	 * @return ���᡼������
	 */
	public function get_image_tag() {
		// ��ꤿ����Хݥ�⡼�ե����ष�Ƥ�
		switch ( $this->feeling) {
			case PluginNikocaleDetail::FEELING_GOOD:
				$src = PLUGIN_NIKOCALE_IMAGE_GOOD;
				break;
			case PluginNikocaleDetail::FEELING_NORMAL:
				$src = PLUGIN_NIKOCALE_IMAGE_NORMAL;
				break;
			case PluginNikocaleDetail::FEELING_BAD:
				$src = PLUGIN_NIKOCALE_IMAGE_BAD;
				break;
		}
		return '<img alt="' . $this->comment . '" src="' . $src . '"'
			. (0 < $this->image_size ? sprintf( " height=%d width=%d", $this->image_size, $this->image_size) : "")
			. " />";
	}
	/**
	 * �Խ����󥫡��դ��Υ��᡼���������֤�
	 * @param	$refer		���ܸ��ڡ���̾
	 * @param	$page_name	�Խ��ڡ���̾
	 * @return	�Խ����󥫡��դ��Υ��᡼������
	 */
	public function get_image_tag_with_anchor( $refer, $page_name) {
		$params = array(
				'plugin' => 'nikocale',
				PLUGIN_NIKOCALE_GET_REFER => $refer,
				PLUGIN_NIKOCALE_GET_EDIT => $page_name,
				PLUGIN_NIKOCALE_FORM_DATE => $this->yyyymmdd,
			);
		return '<a href="' . get_script_uri() . '?' . plugin_nikocale_make_get_param( $params)
			.'">' . $this->get_image_tag() . '</a>';

	}
	/**
	 * ǯ�������֤�
	 * @return ǯ����(YYYYMMDD����)
	 */
	public function get_yyyymmdd() {
		return $this->yyyymmdd;
	}
	/**
	 * ��ʬ���֤�
	 * @return ��ʬ
	 */
	public function get_feeling() {
		return $this->feeling;
	}
	/**
	 * �����Ȥ��֤�
	 * @return ������
	 */
	public function get_comment() {
		return $this->comment;
	}
}

class PluginNikocaleNullDetail extends PluginNikocaleDetail {
	/**
	 * ���󥹥ȥ饯�����������
	 */
	private function PluginNikocaleNullDetail() {
	}
	/**
	 * ���Ȥ�ͣ��Υ��󥹥��󥹤��֤�
	 * @return ���󥹥���
	 */
	protected static function get_instance() {
		static $NULL_FEELING;
		if ( !isset( $NULL_FEELING)) {
			$NULL_FEELING = new PluginNikocaleNullDetail();
		}
		return $NULL_FEELING;
	}
	/**
	 * ���᡼���������֤�
	 ��@return ���᡼������
	 */
	function get_image_tag() {
		return "&nbsp;";
	}
	/**
	 * �Խ����󥫡��դ��Υ��᡼���������֤�
	 * @param	$refer		���ܸ��ڡ���̾
	 * @param	$page_name	�Խ��ڡ���̾
	 * @return	�Խ����󥫡��դ��Υ��᡼������
	 */
	public function get_image_tag_with_anchor( $page, $name) {
		return "&nbsp;";
	}
}

?>
