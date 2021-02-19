<?php
/**
 * @package DB_Cleanup
 * @version 1.0
 */
/*
Plugin Name: DB Cleanup
Plugin URI: https://github.com/fractal512/db-cleanup
Description: Wordpress database cleaning up tool. Peculiarities: searches and removes specific duplicates with meta keys '_pagemeta_title', '_pagemeta_description', '_pagemeta_keywords'.
Author: fractal512
Version: 1.0
Author URI: https://profiles.wordpress.org/fractal512/
*/

// exit if accessed directly
if ( ! defined( 'ABSPATH' ) ) exit;

require_once( dirname(__FILE__).'/inc/class.DBCleanUp.php' );

add_action('admin_init', 'dbclup_init');
function dbclup_init(){
	if ( isset($_GET['page']) && $_GET['page'] == 'dbclup-control' ){
		global $dbclup;
		$dbclup = new DBCleanUp();
	}
	
	if( isset($_GET['action']) ){
		if( $_GET['action'] == 'delete_revisions' ) $dbclup->cleanRevisionPosts();
		if( $_GET['action'] == 'delete_transients' ) $dbclup->cleanTransientOptions();
		if( $_GET['action'] == 'delete_duplicates' ) $dbclup->cleanDupPostmeta();
	}
}

add_action('admin_menu', 'dbclup_control_menu');
function dbclup_control_menu() {
	add_submenu_page('tools.php', 'DB Cleanup', 'DB Cleanup', 'manage_options', 'dbclup-control', 'dbclup_control_page');
}

function dbclup_control_page(){
	global $dbclup;
?>
<h1>DB Cleanup</h1>
<p><em>Плагин очистки базы данных <strong>WordPress</strong>.</em></p>
<?php echo $dbclup->report; ?>
<table cellpadding="10px">
	<tr>
		<td>
			<a class="button button-primary button-hero" href="<?php echo wp_nonce_url(admin_url('tools.php?page=dbclup-control&action=delete_revisions'), 'delete_revisions', 'del_rev_nonce'); ?>">Удалить предыдущие версии</a>
		</td>
		<td>
			Удаляет предыдущие версии контента и связанные с ним метаданные из таблиц "{prefix}_posts", "{prefix}_term_relationships", "{prefix}_postmeta".
			<div class="<?php echo $dbclup->revisionClass; ?>">
				<p>Всего связанных записей в таблицах: <big><?php echo $dbclup->allPosts; ?></big></p>
				<p>Обнаружено связанних записей предыдущих версий контента: <big><?php echo $dbclup->revisionPosts; ?></big></p>
			</div>
		</td>
	</tr>
	<tr>
		<td>
			<a class="button button-primary button-hero" href="<?php echo wp_nonce_url(admin_url('tools.php?page=dbclup-control&action=delete_duplicates'), 'delete_duplicates', 'del_dup_nonce'); ?>">&nbsp;Удалить дубли метаданных&nbsp;</a>
		</td>
		<td>
			Удаляет SEO дубли метаданных, закрепленные за контентом и хранящиеся в таблице "{prefix}_postmeta".
			<div class="<?php echo $dbclup->dupClass; ?>">
				<p>Всего записей в таблице "{prefix}_postmeta": <big><?php echo $dbclup->allPostmeta; ?></big></p>
				<p>Обнаружено дублирующихся метаданных: <big><?php echo $dbclup->dupPostmeta; ?></big></p>
			</div>
		</td>
	</tr>
	<tr>
		<td>
			<a class="button button-primary button-hero" href="<?php echo wp_nonce_url(admin_url('tools.php?page=dbclup-control&action=delete_transients'), 'delete_transients', 'del_tran_nonce'); ?>">Удалить временные данные</a>
		</td>
		<td>
			Очищает таблицу "{prefix}_options" от временных данных.
			<div class="<?php echo $dbclup->transientClass; ?>">
				<p>Всего записей в таблице "{prefix}_options": <big><?php echo $dbclup->allOptions; ?></big></p>
				<p>Обнаружено временных данных: <big><?php echo $dbclup->transientOptions; ?></big></p>
			</div>
		</td>
	</tr>
</table>
<?php
}
