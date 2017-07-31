<?php
/**
 * @package		WHMCS openAPI 
 * @version     1.9
 * @author      Stergios Zgouletas <info@web-expert.gr>
 * @link        http://www.web-expert.gr
 * @copyright   Copyright (C) 2010 Web-Expert.gr All Rights Reserved
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU/GPL
**/
if(!defined("WHMCS")) die("This file cannot be accessed directly");
use Illuminate\Database\Capsule\Manager as Capsule;

class MyCapsule extends Capsule
{
	public function setPdoConnection($currepdontPdo)
	{
		$this->pdo = $pdo;
		return $this;
	}
}

class WOADBCapsule extends MyCapsule
{
	private static $instance;
	protected $conn=null;
	public $counter=0;
	protected $sql_query='';
	protected $useMysqli=false;
	protected $last_query=null;
	
	function __construct()
	{
		parent::__construct()
		$this->connect();
	}
	
	public static function getInstance()
	{
		if(!self::$instance)
		{
			self::$instance = new self();
		}
		return self::$instance;
	}
	
	public function connect()
	{	
		if($this->conn) return true;
		$this->conn = Capsule::connection()->getPdo();
		$this->conn->enableQueryLog();
		$this->setPdoConnection($this->conn);
	}
		
	public function sqlExecute($sql,$data=array())
	{
		$this->conn->setFetchMode(PDO::FETCH_ASSOC);
		
		if(stripos($sql,'SELECT')!==false)
		{
			$result=$this->conn->select($sql,$data);
		}
		elseif(stripos($sql,'UPDATE')!==false)
		{
			$result=$this->conn->update($sql,$data);
		}
		elseif(stripos($sql,'INSERT')!==false)
		{
			$result=$this->conn->insert($sql,$data);
		}
		elseif(stripos($sql,'DELETE')!==false)
		{
			$result=$this->conn->delete($sql,$data);
		}
		else
		{
			$result=$this->conn->statement($sql,$data);
		}
		$this->conn->setFetchMode(PDO::FETCH_CLASS);
		$laQuery = $this->conn->getQueryLog();
		$this->sql_query=$laQuery[0]['query'];
		
		return (array)$result;
	}
	
	//Get Connection
	public function getConnection()
	{
		return $this->conn;
	}
	
	//Escape String
	public function safe($q)
	{
		return addslashes($q)
	}
	
	//Quote Field Name
	public function quoteField($n)
	{
		return '`'.$this->safe($n).'`';
	}
	
	//Quote Key Name
	public function quoteValue($n)
	{
		return $this->conn->quote($this->safe($n));
	}
	
	//Get columns
	public function getColumns($table)
	{
		if(empty($table)) return array();
		$result = $this->query('SHOW COLUMNS FROM '.$this->quoteValue($table).';');
		$columns=array();
		foreach($result as $row)
		{
			$columns[$row['Field']]=$row;
		}
		return $columns;
	}
	
	//Set query
	public function query($q,$data=array())
	{
		$this->last_query=$this->sqlExecute(trim($q),$data);
		$this->counter++;	
		return $this->last_query;
	}
	
	//Delete Row
	public function delete($table,$where=array())
	{
		if(empty($table)) return false;
		$wh=$where;
		$data=array();
		if(is_array($where))
		{
			$wh=array();
			foreach($where as $key=>$value)
			{
				$wh[]=$this->quoteField($key).'=?';
			}
			$wh=@implode(' AND ',$wh);
			$data=array_values($where);
		}
		return $this->query('DELETE FROM '.$this->quoteField($table).' WHERE '.$wh.';',$data);
	}
	
	//Insert Row
	public function insert($table,$fields=array())
	{
		if(empty($table) || !count($fields)) return false;
		$values=$columns=array();
		foreach($fields as $key=>$value)
		{
			$columns[]=$this->quoteField($key);
			$values[]='?';
		}
		return $this->query('INSERT IGNORE INTO `'.$table.'`('.implode(',',$columns).') VALUES('.implode(',',$values).');',array_values($fields));
	}
	
	//Update Row
	public function update($table,$fields=array(),$where=array())
	{
		if(empty($table) || !count($fields)) return false;
		$data=$set=array();
		foreach($fields as $key=>$value) $set[]=$this->quoteField($key).'='.$this->quoteValue($value);
		$wh=$where;
		if(is_array($where))
		{
			$wh=array();
			foreach($where as $key=>$value) $wh[]=$this->quoteField($key).'=?';
			$wh=@implode(' AND ',$wh);
			$data=array_values($where);
		}
		return $this->query('UPDATE `'.$table.'` SET '.implode(', ',$set).' WHERE '.$wh.';',$data);
	}
	
	//Get query
	public function getSQL()
	{
		return $this->sql_query;
	}
	
	//Single column
	public function getValue($q)
	{		
		$q=trim(rtrim($q,';'));
		if(stripos($q,'LIMIT')!==FALSE)
		{
			$q=substr($q, 0,stripos($q,'LIMIT'));
		}
		$q.=' LIMIT 1;';
		$result=$this->query($q);
		return reset($result);
	}
	
	//Get Single Row
	public function getRow($q)
	{
		$q=trim(rtrim($q,';'));
		if(stripos($q,'LIMIT')!==FALSE)
		{
			$q=substr($q, 0,stripos($q,'LIMIT'));
		}
		$q.=' LIMIT 1;';
		$result=$this->query($q);		
		return $result;
	}
	
	//Fetch Array
	public function fetch_array($rs=null,$type ='MYSQL_ASSOC')
	{
		return $this->last_query;
	}
	
	//Fetch row
	public function fetch_row($rs=null)
	{
		return $this->last_query;
	}
	
	//get last insert id
	public function insert_id($rs=null)
	{
		return $this->conn->lastInsertId();
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