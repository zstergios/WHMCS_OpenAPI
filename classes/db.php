<?php
if(!defined("WHMCS")) die("This file cannot be accessed directly");

class WDB{
	private static $instance;
	protected $conn=null;
	protected $mysqlActive=true;
	public $counter=0;
	protected $useMysqli=false;
	protected $last_query=null;
	public $charset=null;
	function __construct(){
		$this->connect();
	}
	
	/*Get One Instance of Database*/
	public static function getInstance() {
		if(!self::$instance) {
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	//Connect to database if no connection found
	public function connect(){	
		if($this->conn) return true;
		$configfile=ROOTDIR.DIRECTORY_SEPARATOR.'configuration.php';
		if(!file_exists($configfile)) exit("configuration.php not found at ".$configfile);
		require($configfile);
		
		//Check if there is already connection
		if(!@mysql_query('SELECT COUNT(*) AS sum FROM `tblconfiguration')){
			$this->mysqlActive=false;
		}
		
		//Check if Mysqli is available
		if(function_exists("mysqli_connect") && !$this->mysqlActive){ 
			$this->useMysqli=true;
		}
		
		//Fix password issue
		if(empty($db_password)) $db_password=NULL;
		
		#Connect to DB
		if(!$this->mysqlActive){
			if($this->useMysqli)	
				$this->conn = mysqli_connect($db_host,$db_username,$db_password);
			else
				$this->conn = mysql_connect($db_host,$db_username,$db_password);
			
			#Check Connect
			if(!$this->conn){
				exit($this->error());
			}
			
			#SelectDB
			if($this->useMysqli){		
				@mysqli_select_db($this->conn,$db_name);
			}else{
				@mysql_select_db($db_name,$this->conn);
			}
			
			$err=$this->error();
			
			if(!empty($err)){
				exit("Select DB Error:". $err);
			}
			
			#Charset
			if(isset($mysql_charset)){
				$this->charset=$mysql_charset;
				if($this->useMysqli)
					mysqli_set_charset($this->conn,$mysql_charset);
				else
					mysql_set_charset($mysql_charset,$this->conn);
			}
		}	
	}
	
	//Get Connection
	public function getConnection(){
		return $this->conn;
	}
	
	//Escape String
	public function safe($q){
		if($this->useMysqli) 
			return mysqli_real_escape_string($this->conn,$q);
		else 
			return mysql_real_escape_string($q);
	}
	
	//Quote Field Name
	public function quoteField($n)
	{
		return '`'.$this->safe($n).'`';
	}
	
	//Quote Key Name
	public function quoteValue($n)
	{
		return '"'.$this->safe($n).'"';
	}
	
	//Get columns
	public function getColumns($table){
		if(empty($table)) return array();
		$rs = $this->query("SHOW COLUMNS FROM `".$table."`;");
		$columns=array();
		while ($row = $this->fetch_array($rs))
		{
			$columns[$row['Field']]=$row;
		}
		return $columns;
	}
	
	//Set query
	public function query($q){
		if($this->useMysqli){ 
			$this->last_query=mysqli_query($this->conn,trim($q));
		}else{
			$this->last_query=mysql_query(trim($q));
		}
		if(!$this->last_query){
			if((int)$_REQUEST["debug"]==1) echo "<div style=\"border:1px dooted red;\">Error on Query: ".$q."\n".$this->error()."</div>";
		}
		$this->counter++;	
		return $this->last_query;
	}
	
	//Delete Row
	public function insert($table,$where=array()){
		if(empty($table)) return false;
		foreach($fields as $key=>$value) $set[]=$this->quoteField($key).'='.$this->quoteValue($value);
		$wh=$where;
		if(is_array($where)){
			$wh=array();
			foreach($where as $key=>$value) $wh[]=$this->quoteField($key).'='.$this->quoteValue($value);
			$wh=@implode(' AND ',$wh);
		}
		//if(empty($wh)) return false;
		return $this->query('DELETE FROM '.$this->quoteField($table).' WHERE '.$wh.';');
	}
	
	//Insert Row
	public function insert($table,$fields=array()){
		if(empty($table) || !count($fields)) return false;
		$values=$columns=array();
		foreach($fields as $key=>$value){
			$columns[]=$this->quoteField($key);
			$values[]=$this->quoteValue($value);
		}
		return $this->query('INSERT IGNORE INTO `'.$table.'`('.implode(',',$columns).') VALUES('.implode(',',$values).');');
	}
	
	//Update Row
	public function update($table,$fields=array(),$where=array()){
		if(empty($table) || !count($fields)) return false;
		$set=array();
		foreach($fields as $key=>$value) $set[]=$this->quoteField($key).'='.$this->quoteValue($value);
		$wh=$where;
		if(is_array($where)){
			$wh=array();
			foreach($where as $key=>$value) $wh[]=$this->quoteField($key).'='.$this->quoteValue($value);
			$wh=@implode(' AND ',$wh);
		}
		//if(empty($wh)) return false;
		return $this->query('UPDATE `'.$table.'` SET '.implode(', ',$set).' WHERE '.$wh.';');
	}
	
	//Get query
	public function getSQL(){
		return $this->sql_query;
	}
	
	//Get Single Row
	public function getRow($q){
		$this->last_query=$this->query($q);
		return $this->fetch_array($this->last_query);
	}
	
	//Fetch Array
	public function fetch_array($rs=null,$type=MYSQLI_ASSOC){
		if(!$rs) $rs=$this->last_query;
		if($this->useMysqli)
			return mysqli_fetch_array($rs,$type);
		else
			return mysql_fetch_array($rs,$type);
	}
	
	//Fetch row
	public function fetch_row($rs=null){
		if(!$rs) $rs=$this->last_query;
		if($this->useMysqli)
			return mysqli_fetch_row($rs);
		else
			return mysql_fetch_row($rs);
	}
	
	//get last insert id
	public function insert_id($rs=null){
		if(!$rs) $rs=$this->last_query;
		if($this->useMysqli)
			return mysqli_insert_id($this->conn);
		else
			return mysql_insert_id();
	}
	
	//get number of fields
	public function num_fields($rs=null){
		if(!$rs) $rs=$this->last_query;
		if($this->useMysqli)
			return (int)mysqli_num_fields($rs);
		else
			return (int)mysql_num_fields($rs);
	}	
	
	//get number of rows
	public function num_rows($rs=null){
		if(!$rs) $rs=$this->last_query;
		if($this->useMysqli)
			return (int)mysqli_num_rows($rs);
		else
			return (int)mysql_num_rows($rs);
	}
	//get affected rows (insert/update/delete)
	public function affected_rows(){
		if($this->useMysqli) return (int)@mysqli_affected_rows($this->conn);
		return (int)@mysql_affected_rows();
	}
	
	//Get error
	public function error($rs=null){
		if($this->useMysqli)
			return mysqli_error($this->conn);
		else
			return mysql_error($this->conn);
	}
	//close connection
	public function close(){
		if($this->conn){
			if($this->useMysqli)
				mysqli_close($this->conn);
			else
				mysql_close($this->conn);
			$this->conn=null;
		}
	}
	
	/*
		Import SQL data
	*/
	public function source_query($dbms_schema){
		@set_time_limit(0);
		@ini_set('memory_limit', '10000M');

		$sql_query = @fread(@fopen($dbms_schema, 'r'), @filesize($dbms_schema)) or exit('problem with '.$dbms_schema);
		$sql_query = $this->remove_remarks($sql_query);
		$sql_query = $this->split_sql_file($sql_query, ';');
		
		foreach($sql_query as $sql){
			$this->query($sql);
		}
	}
	
	protected function remove_comments(&$output)
	{
	   $lines = explode("\n", $output);
	   $output = "";

	   // try to keep mem. use down
	   $linecount = count($lines);

	   $in_comment = false;
	   for($i = 0; $i < $linecount; $i++)
	   {
		  if( preg_match("/^\/\*/", preg_quote($lines[$i])) )
		  {
			 $in_comment = true;
		  }

		  if( !$in_comment )
		  {
			 $output .= $lines[$i] . "\n";
		  }

		  if( preg_match("/\*\/$/", preg_quote($lines[$i])) )
		  {
			 $in_comment = false;
		  }
	   }

	   unset($lines);
	   return $output;
	}

	//
	// remove_remarks will strip the sql comment lines out of an uploaded sql file
	//
	protected function remove_remarks($sql)
	{
	   $lines = explode("\n", $sql);

	   // try to keep mem. use down
	   $sql = "";

	   $linecount = count($lines);
	   $output = "";

	   for ($i = 0; $i < $linecount; $i++)
	   {
		  if (($i != ($linecount - 1)) || (strlen($lines[$i]) > 0))
		  {
			 if (isset($lines[$i][0]) && $lines[$i][0] != "#")
			 {
				$output .= $lines[$i] . "\n";
			 }
			 else
			 {
				$output .= "\n";
			 }
			 // Trading a bit of speed for lower mem. use here.
			 $lines[$i] = "";
		  }
	   }

	   return $output;

	}

	//
	// split_sql_file will split an uploaded sql file into single sql statements.
	// Note: expects trim() to have already been run on $sql.
	//
	protected function split_sql_file($sql, $delimiter=';')
	{
	   // Split up our string into "possible" SQL statements.
	   $tokens = explode($delimiter, $sql);

	   // try to save mem.
	   $sql = "";
	   $output = array();

	   // we don't actually care about the matches preg gives us.
	   $matches = array();

	   // this is faster than calling count($oktens) every time thru the loop.
	   $token_count = count($tokens);
	   for ($i = 0; $i < $token_count; $i++)
	   {
		  // Don't wanna add an empty string as the last thing in the array.
		  if (($i != ($token_count - 1)) || (strlen($tokens[$i] > 0)))
		  {
			 // This is the total number of single quotes in the token.
			 $total_quotes = preg_match_all("/'/", $tokens[$i], $matches);
			 // Counts single quotes that are preceded by an odd number of backslashes,
			 // which means they're escaped quotes.
			 $escaped_quotes = preg_match_all("/(?<!\\\\)(\\\\\\\\)*\\\\'/", $tokens[$i], $matches);

			 $unescaped_quotes = $total_quotes - $escaped_quotes;

			 // If the number of unescaped quotes is even, then the delimiter did NOT occur inside a string literal.
			 if (($unescaped_quotes % 2) == 0)
			 {
				// It's a complete sql statement.
				$output[] = $tokens[$i];
				// save memory.
				$tokens[$i] = "";
			 }
			 else
			 {
				// incomplete sql statement. keep adding tokens until we have a complete one.
				// $temp will hold what we have so far.
				$temp = $tokens[$i] . $delimiter;
				// save memory..
				$tokens[$i] = "";

				// Do we have a complete statement yet?
				$complete_stmt = false;

				for ($j = $i + 1; (!$complete_stmt && ($j < $token_count)); $j++)
				{
				   // This is the total number of single quotes in the token.
				   $total_quotes = preg_match_all("/'/", $tokens[$j], $matches);
				   // Counts single quotes that are preceded by an odd number of backslashes,
				   // which means they're escaped quotes.
				   $escaped_quotes = preg_match_all("/(?<!\\\\)(\\\\\\\\)*\\\\'/", $tokens[$j], $matches);

				   $unescaped_quotes = $total_quotes - $escaped_quotes;

				   if (($unescaped_quotes % 2) == 1)
				   {
					  // odd number of unescaped quotes. In combination with the previous incomplete
					  // statement(s), we now have a complete statement. (2 odds always make an even)
					  $output[] = $temp . $tokens[$j];

					  // save memory.
					  $tokens[$j] = "";
					  $temp = "";

					  // exit the loop.
					  $complete_stmt = true;
					  // make sure the outer loop continues at the right point.
					  $i = $j;
				   }
				   else
				   {
					  // even number of unescaped quotes. We still don't have a complete statement.
					  // (1 odd and 1 even always make an odd)
					  $temp .= $tokens[$j] . $delimiter;
					  // save memory.
					  $tokens[$j] = "";
				   }

				} // for..
			 } // else
		  }
	   }

	   return $output;
	}
}