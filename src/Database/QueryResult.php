<?php 

namespace Selvi\Database;
use mysqli_result;

class QueryResult {

    private $result;
	
	public function __construct($query_result)
	{
        $this->result = $query_result;
    }
	
	public function result()
	{
        if(is_bool($this->result)) {
            return $this->result;
        }

        if($this->result instanceof mysqli_result) {
            $result = array();
            while($row = $this->result->fetch_object()){
                $result[] = $row;
            }
            return $result;
        }

        return null;
    }
    
    public function row() {
        if(is_bool($this->result)) {
            return $this->result;
        }

        if($this->result instanceof mysqli_result) {
            return $this->result->fetch_object();
        }

        return null;
    }
	
	public function num_rows()
	{
		return $this->result->num_rows;
	}

}