<?php

class DBCleanUp {
	
	private $wpdbObj;
	public $allPosts = 0;
	public $revisionPosts = 0;
	public $revisionClass = 'updated';
	public $allOptions = 0;
	public $transientOptions = 0;
	public $transientClass = 'updated';
	public $allPostmeta = 0;
	public $dupPostmeta = 0;
	public $dupClass = 'updated';
	public $report = "";
		
	public function __construct(){
		global $wpdb;
		$this->wpdbObj =& $wpdb;
		$this->countAllPosts();
		$this->countRevisionPosts();
		$this->countAllOptions();
		$this->countTransientOptions();
		$this->countAllPostmeta();
		$this->countDupPostmeta();
	}
	
	public function countAllPosts(){
		$totalEntriesNum = 0;
		
		$query = "SELECT ID FROM {$this->wpdbObj->posts}";
		$postIdsArr = $this->wpdbObj->get_col( $query );
		$totalEntriesNum += count( $postIdsArr );
		$postIdsStr = implode( ', ', $postIdsArr );
		
		if( $totalEntriesNum > 0 ){
			$query = "SELECT COUNT(*) FROM {$this->wpdbObj->term_relationships} WHERE object_id IN ($postIdsStr)";
			$totalEntriesNum += $this->wpdbObj->get_var($query);
			$query = "SELECT COUNT(*) FROM {$this->wpdbObj->postmeta} WHERE post_id IN ($postIdsStr)";
			$totalEntriesNum += $this->wpdbObj->get_var($query);
		}
		
		$this->allPosts = $totalEntriesNum;
	}

	private function countRevisionPosts(){
		$totalEntriesNum = 0;
		
		$query = "SELECT ID FROM {$this->wpdbObj->posts} WHERE post_type = 'revision'";
		$postIdsArr = $this->wpdbObj->get_col( $query );
		$totalEntriesNum += count( $postIdsArr );
		$postIdsStr = implode( ', ', $postIdsArr );
		
		if( $totalEntriesNum > 0 ){
			$query = "SELECT COUNT(*) FROM {$this->wpdbObj->term_relationships} WHERE object_id IN ($postIdsStr)";
			$totalEntriesNum += $this->wpdbObj->get_var($query);
			$query = "SELECT COUNT(*) FROM {$this->wpdbObj->postmeta} WHERE post_id IN ($postIdsStr)";
			$totalEntriesNum += $this->wpdbObj->get_var($query);
		}
		
		$this->revisionPosts = $totalEntriesNum;
		$this->revisionClass = ($totalEntriesNum > 0) ? 'error' : 'updated';
	}
	
	public function cleanRevisionPosts(){
		if ( !$this->securityPass('delete_revisions', 'del_rev_nonce') ) return;
		$this->enableWPMaintenance();
		
		$query="
		DELETE a,b,c
		FROM {$this->wpdbObj->posts} a
		LEFT JOIN {$this->wpdbObj->term_relationships} b ON ( a.ID = b.object_id )
		LEFT JOIN {$this->wpdbObj->postmeta} c ON ( a.ID = c.post_id )
		WHERE a.post_type = 'revision'
		";
		
		$response = $this->wpdbObj->query($query);
		$this->optimizeTables();
		
		if($response === false){
			$this->report = '<div class="error"><p>Ошибка! Произошла ошибка при работе с базой данних во время выполнения запроса очистки таблиц "{prefix}_posts", "{prefix}_term_relationships" и "{prefix}_postmeta.</p></div>';
		}
		else{
			$this->report = '<div class="updated"><p>Операция выполнена успешно! Удалено записей: '.$response.'</p></div>';
			$this->countAllPosts();
			$this->countRevisionPosts();
		}
		
		$this->disableWPMaintenance();
	}
	
	private function countAllOptions(){
		$query = "SELECT COUNT(*) FROM {$this->wpdbObj->options}";
		$this->allOptions = $this->wpdbObj->get_var($query);
	}
	
	private function countTransientOptions(){
		$query = "SELECT COUNT(*) FROM {$this->wpdbObj->options} WHERE option_name LIKE ('%\_transient\_%')";
		$this->transientOptions = $this->wpdbObj->get_var($query);
		$this->transientClass = ($this->transientOptions > 30) ? 'error' : 'updated';
	}
	
	public function cleanTransientOptions(){
		if ( !$this->securityPass('delete_transients', 'del_tran_nonce') ) return;
		$this->enableWPMaintenance();
		
		$query = "DELETE FROM {$this->wpdbObj->options} WHERE option_name LIKE ('%\_transient\_%')";
		$response = $this->wpdbObj->query($query);
		$this->optimizeTables();
		
		if($response === false){
			$this->report = '<div class="error"><p>Ошибка! Произошла ошибка при работе с базой данних во время выполнения запроса очистки таблицы {prefix}_options.</p></div>';
		}
		else{
			$this->report = '<div class="updated"><p>Операция выполнена успешно! Удалено временных записей: '.$response.'</p></div>';
			$this->countAllOptions();
			$this->countTransientOptions();
		}
		
		$this->disableWPMaintenance();
	}

	private function countAllPostmeta(){
		$query = "SELECT COUNT(*) FROM {$this->wpdbObj->postmeta}";
		$this->allPostmeta = $this->wpdbObj->get_var($query);
	}
	
	private function countDupPostmeta(){
		//$query = "SELECT COUNT(*) FROM {$this->wpdbObj->postmeta} WHERE meta_key IN ('_pagemeta_title', '_pagemeta_description', '_pagemeta_keywords')";
		$query = "SELECT (COUNT(*)-3) FROM {$this->wpdbObj->postmeta} WHERE meta_key IN ('_pagemeta_title', '_pagemeta_description', '_pagemeta_keywords') GROUP BY post_id";
		$this->dupPostmeta = array_sum($this->wpdbObj->get_col($query));
		$this->dupClass = ($this->dupPostmeta > 0) ? 'error' : 'updated';
	}
	
	public function cleanDupPostmeta(){
		if ( !$this->securityPass('delete_duplicates', 'del_dup_nonce') ) return;
		$this->enableWPMaintenance();
		
		$meta_keys = array('_pagemeta_title', '_pagemeta_description', '_pagemeta_keywords');
		$responses = 0;
		foreach($meta_keys as $meta_key){
			$query = "SELECT MAX(meta_id) FROM {$this->wpdbObj->postmeta} WHERE meta_key = '{$meta_key}' GROUP BY post_id";
			$ids = $this->wpdbObj->get_col( $query );
			$idsStr = implode( ', ', $ids );
			$query = "DELETE FROM {$this->wpdbObj->postmeta} WHERE meta_key = '{$meta_key}' AND meta_id NOT IN ({$idsStr})";
			$response = $this->wpdbObj->query($query);
			if($response === false){
				$this->report = '<div class="error"><p>Ошибка! Произошла ошибка при работе с базой данних во время выполнения запроса очистки таблицы {prefix}_postmeta.</p></div>';
				return;
			}
			$responses += $response;
		}
		$this->optimizeTables();
		
		$this->report = '<div class="updated"><p>Операция выполнена успешно! Удалено дублирующихся записей: '.$responses.'</p></div>';
		$this->countAllPostmeta();
		$this->countDupPostmeta();
		$this->countAllPosts();
		$this->countRevisionPosts();
		
		$this->disableWPMaintenance();
	}
	
	private function securityPass($action, $nonce){
		/* if (!isset($_GET[$nonce]) || !wp_verify_nonce($_GET[$nonce], $action)) {
			$this->report = '<div class="error"><p>Ошибка! Операция отменена. Предотвращена попытка выполнения CSRF атаки.</p></div>';
			return false;
		} */
		
		check_admin_referer( $action, $nonce );
		
		if ( !current_user_can('manage_options') ) {
			$this->report = '<div class="error"><p>Ошибка! Вы не имеете соответствующих прав для выполнения данной операции! Очищать базу данных могут только администраторы.</p></div>';
			return false;
		}
		
		return true;
	}
	
	private function enableWPMaintenance(){
		$filename = ABSPATH . ".maintenance";
		
		$string = '<?php $upgrading = '.time().'; ?>';
		$fp = fopen($filename, "w"); // Создаем файл и открываем его в режиме записи
		if(!$fp){
			$this->report = '<div class="error"><p>Ошибка доступа к файлу! Невозможно перевести сайт в режим обслуживания.</p></div>';
			return;
		}
		fwrite($fp, $string); // Запись в файл
		fclose($fp); //Закрытие файла
	}
	
	private function disableWPMaintenance(){
		$filename = ABSPATH . ".maintenance";
		
		if (file_exists($filename)) {
			unlink($filename);
		}
	}
	
	private function optimizeTables(){
		$query = "SHOW TABLE STATUS WHERE Data_free > 0";
		$tables = $this->wpdbObj->get_col($query);
		
		foreach ($tables as $table){
			$query = "OPTIMIZE TABLE $table";
			$this->wpdbObj->query($query);
		}
	}
	
}
