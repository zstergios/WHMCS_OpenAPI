<?php
/**
 * @package		WHMCS openAPI 
 * @version     3.0.1
 * @author      Stergios Zgouletas | WEB EXPERT SERVICES LTD <info@web-expert.gr>
 * @link        http://www.web-expert.gr
 * @copyright   Copyright (C) 2010 Web-Expert.gr All Rights Reserved
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
**/
if(!defined("WHMCS")) die("This file cannot be accessed directly");

class WOADB{
	private static $instance;
	//Connection
	protected $conn=null;
	protected $mysqlActive=true;
	protected $useMysqli=false;
	
	//Query
	public $counter=0;
	protected $last_query=null;
	protected $enableErrors=0;
	public $enableExplain=0;
	protected $sql_query='';
	
	//DB
	private $host = '127.0.0.1';
	private $dbname;
	private $dbuser;
	private $dbpass;
	private $charset=null;
		
	/*Get One Instance of Database*/
	public static function getInstance()
	{
		if(!self::$instance) self::$instance = new self();
		return self::$instance;
	}
		
	function __construct()
	{
		if(defined("WHMCS")) $this->loadFromConfig(ROOTDIR,'configuration.php');
	}
	
	function __destruct()
	{
		$this->close();
	}
	
	public function loadFromConfig($location=ROOTDIR,$file='configuration.php')
	{
		//security check
		$pattern = '/^[A-Za-z0-9_\/-]+[A-Za-z0-9_\.-]*([\\\\\/][A-Za-z0-9_-]+[A-Za-z0-9_\.-]*)*$/';
		preg_match($pattern,$location.DIRECTORY_SEPARATOR.$file, $matches);
		$configfile = isset($matches[0]) ? (string) $matches[0] : '';
		
		if(empty($configfile) || !file_exists($configfile)) exit($file. " not found at ".$configfile);
		require($configfile);
		
		$this->host = isset($db_host)? $db_host : (isset($host)?$host:'127.0.0.1');
		$this->dbname = isset($db_name)? $db_name : (isset($dbname)?$dbname:NULL);
		$this->dbuser = isset($db_username)? $db_username : (isset($dbuser)?$dbuser:NULL);
		$this->dbpass = isset($db_password)? $db_password : (isset($dbpass)?$dbpass:NULL);
		$this->charset = isset($mysql_charset)? $mysql_charset : (isset($charset)?$charset:NULL);
		
		$this->connect();
	}
	
	public function setConnection($host,$dbname,$dbuser,$dbpass=NULL,$charset=NULL)
	{
		$this->host=$host;
		$this->dbname=$dbname;
		$this->dbuser=$dbuser;
		$this->dbpass=$dbpass;
		$this->charset=$charset;
		$this->connect();
	}
	
	public function display_errors($mode)
	{	
		$this->enableErrors=(int)$mode;
	}
		
	//Connect to database if no connection found
	public function connect()
	{	
		if($this->conn) return true;
				
		//Check if there is already connection
		if(!function_exists('@mysql_query') || !@mysql_query('SELECT 1'))
		{
			$this->mysqlActive=false;
		}
		
		//Check if Mysqli is available
		if(function_exists("mysqli_connect") && !$this->mysqlActive)
		{ 
			$this->useMysqli=true;
		}
				
		#Connect to DB
		if(!$this->mysqlActive && !empty($this->host) && !empty($this->dbname) && !empty($this->dbuser))
		{
			//Fix password issue
			if(empty($this->dbpass)) $this->dbpass=NULL;
			
			if($this->useMysqli)	
				$this->conn = mysqli_connect($this->host,$this->dbuser,$this->dbpass);
			else
				$this->conn = @mysql_connect($this->host,$this->dbuser,$this->dbpass);
			
			#Check Connect
			if(!$this->conn)
			{
				exit($this->error());
			}
			
			#SelectDB
			if($this->useMysqli)
			{		
				@mysqli_select_db($this->conn,$this->dbname);
			}
			else
			{
				@mysql_select_db($this->dbname,$this->conn);
			}
			
			$err=$this->error();
			
			if(!empty($err))
			{
				exit("Select DB Error:". $err); //critical error
			}
			
			#Charset
			if(!empty($this->charset)) $this->setCharset($this->charset);
		}
	}
	
	public function setCharset($charset='utf8')
	{
		$this->charset=$charset;
		$result=$this->useMysqli?mysqli_set_charset($this->conn,$this->charset):@mysql_set_charset($this->charset,$this->conn);
		if(!$result && $this->enableErrors)
		{
			echo "Error loading character set ".$this->charset.":". $this->error();
		}
		return $result;
	}
	
	
	//Get Connection
	public function getConnection()
	{
		return $this->conn;
	}
	
	//Escape String
	public function safe($q)
	{
		if($this->useMysqli) 
			return mysqli_real_escape_string($this->conn,$q);
		else 
			return @mysql_real_escape_string($q);
	}
	
	//Quote Field Name
	public function quoteField($n)
	{
		$prefix='';
		$field=$this->safe($n);
		if(strpos($field,'.')!==false)
		{
			list($prefix,$field)=explode('.',$n,2);
			$prefix.='.';
		}		
		return $prefix.'`'.$this->safe($field).'`';
	}
	
	//Quote Key Name
	public function quoteValue($n)
	{
		return '"'.$this->safe($n).'"';
	}
	
	//Get columns
	public function getColumns($table)
	{
		if(empty($table)) return array();
		$rs = $this->query('SHOW COLUMNS FROM '.$this->quoteField($table).';');
		$columns=array();
		while ($row = $this->fetch_array($rs))
		{
			$columns[$row['Field']]=$row;
		}
		return $columns;
	}
	
	//Set query
	public function query($q)
	{
		$this->sql_query=trim($q);
		
		$startTime=time();
		if($this->useMysqli)
		{ 
			$this->last_query=mysqli_query($this->conn,$this->sql_query);
		}
		else
		{
			$this->last_query=@mysql_query($this->sql_query);
		}
		
		$log=array('time'=>round(time() - $startTime,4),'sql'=>$this->sql_query);
		if($this->last_query && $this->enableExplain && substr($this->sql_query,0,6)=='SELECT')
		{
			$log['explain']=$this->explain($this->sql_query);
		}
		
		$this->queries[]=$log;
		
		if(!$this->last_query && $this->enableErrors)
		{
			echo "<div style=\"border:1px dotted red;\">Error on Query: <i>".$this->sql_query."</i>\n".$this->error()."</div>";
		}
		$this->counter++;	
		return $this->last_query;
	}
	
	public function explain($q)
	{
		$backup=$this->last_query;
		$this->enableExplain=false;
		
		$explainData=$this->getArray("EXPLAIN ".$q);
		
		$this->last_query=$backup;
		$this->enableExplain=true;
		return $explainData;
	}
	
	
	//Build SQL
	public function buildSQL($table,$select='*',$where=array())
	{
		if(empty($table)) return false;
		$wh=$where;
		if(is_array($where))
		{
			$wh=array();
			foreach($where as $key=>$value) $wh[]=$this->quoteField($key).$this->getValueStatement($value);
			$wh=@implode(' AND ',$wh);
		}
		
		if(is_array($select)) $select=implode(', ',$select);
		return 'SELECT '.$select.' FROM '.$this->quoteField($table).' WHERE '.$wh.';';
	}
	
	//Select Row
	public function select($table,$select='*',$where=array())
	{
		return $this->query($this->buildSQL($table,$select,$where));
	}
	
	
	private function getValueStatement($value)
	{
		if(is_array($value))
		{
			$data=array_map(array($this, 'safe'), $value);
			return ' IN ('.implode(',',$data).')';
		}
		else
		{
			return $this->isNULL($value)?' is NULL':' = '.$this->quoteValue($value);
		}
	}
	
	//Delete Row
	public function delete($table,$where=array()){
		if(empty($table)) return false;
		$wh=$where;
		if(is_array($where))
		{
			$wh=array();
			foreach($where as $key=>$value)
			{
				$wh[]=$this->quoteField($key).$this->getValueStatement($value);

			}
			$wh=@implode(' AND ',$wh);
		}
		//if(empty($wh)) return false;
		return $this->query('DELETE FROM '.$this->quoteField($table).' WHERE '.$wh.';');
	}
	
	//Insert Row
	public function insert($table,$fields=array())
	{
		if(empty($table) || !count($fields)) return false;
		$values=$columns=array();
		foreach($fields as $key=>$value)
		{
			$columns[]=$this->quoteField($key);
			$values[]=$this->quoteValue($value);
		}
		return $this->query('INSERT IGNORE INTO `'.$table.'`('.implode(',',$columns).') VALUES('.implode(',',$values).');');
	}
	
	private function isNULL($value)
	{
		return (!is_numeric($value) && ($value===NULL || strtoupper($value)=='NULL'))?true:false;
	}
	
	//Update Row
	public function update($table,$fields=array(),$where=array())
	{
		if(empty($table) || !count($fields)) return false;
		$set=array();

		foreach($fields as $key=>$value)
		{
			$set[]=$this->quoteField($key).$this->getValueStatement($value);
		}
		
		$wh=$where;
		if(is_array($where))
		{
			$wh=array();
			foreach($where as $key=>$value)
			{
				$set[]=$this->quoteField($key).'='.($this->isNULL($value)?'NULL':$this->quoteValue($value));
			}
			$wh=@implode(' AND ',$wh);
		}
		//if(empty($wh)) return false;
		return $this->query('UPDATE `'.$table.'` SET '.implode(', ',$set).' WHERE '.$wh.';');
	}
	
	//Get query
	public function getSQL()
	{
		return $this->sql_query;
	}
	
	//Single column
	public function getValue($q)
	{		
		$data=$this->fetch_array($this->query($q),'MYSQL_NUM');
		if(empty($data) || !is_array($data)) $data=array(0=>NULL);
		return $data[0];
	}
	
	//Get Single Row
	public function getRow($q)
	{
		$data=$this->fetch_array($this->query($q));
		if(empty($data) || !is_array($data)) $data=array();
		return $data;
	}
	
	//Get Single Row
	public function getArray($q,$key=NULL,$id=NULL)
	{
		$return=array();
		$rs=$this->query($q);
		while($r=$this->fetch_array($rs))
		{
			$value=empty($key)?$r:$r[$key];
			if($id===NULL) 
				$return[]=$value;
			else 
				$return[$r[$id]]=$value;
		}
		return $return;
	}
	
	public function fetch_array($rs=null,$type ='MYSQL_ASSOC')
	{
		if(is_null($rs)) $rs=$this->last_query;
		$type=strtoupper($type);
		if($this->useMysqli)
		{
			$type=str_replace('MYSQL','MYSQLI',$type);
		}
		if($rs === FALSE) return false;
		if($this->useMysqli)
			return mysqli_fetch_array($rs,constant($type));
		else
			return @mysql_fetch_array($rs,constant($type));
	}
	
	//Fetch row
	public function fetch_row($rs=null)
	{
		if(is_null($rs)) $rs=$this->last_query;
		if($this->useMysqli)
			return mysqli_fetch_row($rs);
		else
			return @mysql_fetch_row($rs);
	}
	
	//get last insert id
	public function insert_id($rs=null)
	{
		if(is_null($rs)) $rs=$this->last_query;
		if($this->useMysqli)
			return mysqli_insert_id($this->conn);
		else
			return @mysql_insert_id();
	}
	
	//get number of fields
	public function num_fields($rs=null)
	{
		if(is_null($rs)) $rs=$this->last_query;
		if($this->useMysqli)
			return (int)mysqli_num_fields($rs);
		else
			return (int)@mysql_num_fields($rs);
	}	
	
	//get number of rows
	public function num_rows($rs=null)
	{
		if(is_null($rs)) $rs=$this->last_query;
		if($this->useMysqli)
			return (int)mysqli_num_rows($rs);
		else
			return (int)@mysql_num_rows($rs);
	}
	
	//get affected rows (insert/update/delete)
	public function affected_rows()
	{
		if($this->useMysqli) return (int)@mysqli_affected_rows($this->conn);
		return (int)@mysql_affected_rows($this->conn);
	}
	
	//Get error
	public function error($rs=null)
	{
		if($this->useMysqli)
			return mysqli_error($this->conn);
		else
			return @mysql_error($this->conn);
	}
	
	//close connection
	public function close()
	{
		if($this->conn)
		{
			if($this->useMysqli)
				mysqli_close($this->conn);
			else
				@mysql_close($this->conn);
			$this->conn=null;
		}
	}
	
	/*
		Import SQL data
	*/
	public function source_query($dbms_schema)
	{
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