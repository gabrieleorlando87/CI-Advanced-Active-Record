<?php

namespace CIAdvancedActiveRecord;

/**
 * Description of base_model
 * 
 * @Version: 1.0
 *
 * @author Gabriele
 */
class Advanced_Model extends \CI_Model {

	const SELECT = 'SELECT';
	const FROM = 'FROM';
	const JOIN = 'JOIN';
	const LEFT_JOIN = 'LEFT_JOIN';
	const WHERE = 'WHERE';
	const OR_WHERE = 'OR_WHERE';
	const WHERE_NOT_IN = 'WHERE_NOT_IN';
	const LIKE = 'LIKE';
	const ORDER_BY = 'ORDER_BY';
	const START = 'START';
	const LIMIT = 'LIMIT';

    public function __construct() {
        parent::__construct();
    }

    public function __call($name, $arguments) {
        $nome_explode_delimitatore = '_';
        $nome_explode = explode($nome_explode_delimitatore, $name);

        if (count($nome_explode) == 0) {
            throw new \Exception('Funzione non indicata correttamente.');
        }

        $metodo = '';
        $tabella = '';
        foreach ($nome_explode as $key => $value) {
            if ($key == 0) {
                $metodo = $value;
            } else {
                if (trim($tabella) == '') {
                    if(trim($value) == ''){
                        $tabella = $nome_explode_delimitatore;
                    }else{
                        $tabella = $value;
                    }
                } else {
                    if($tabella == $nome_explode_delimitatore){
                        $tabella =  $tabella . $value;
                    }else{
                        $tabella = $tabella . $nome_explode_delimitatore . $value;
                    }
                    
                }
            }
        }

        if (trim($metodo) == '') {
            throw new \Exception('Metodo non indicato correttamente.');
        }
        if (trim($tabella) == '') {
            throw new \Exception('Tabella non indicata correttamente.');
        }

        if ($metodo != 'select' && $metodo != 'insert' && $metodo != 'update' && $metodo != 'delete' && $metodo != 'count' && $metodo != 'sum') {
            throw new \Exception('Metodo non indicato correttamente. [Ammessi: select,insert,update,delete,count,sum]');
        }
		
		if($metodo == 'select' || $metodo == 'count' || $metodo == 'sum'){
			$arguments[0][self::FROM] = $tabella;
		}
		if($metodo == 'insert' || $metodo == 'update' || $metodo == 'delete'){
			$arguments['table'] = $tabella;
		}
		
        return call_user_func_array(array($this, $nome_explode_delimitatore . $metodo), $arguments);
    }
	
	private function _select($queryArray) {
//        $queryArray = [
//			self::SELECT => '*',
//			self::FROM => '',
//			self::JOIN => [],
//			self::LEFT_JOIN => [],
//			self::WHERE =>[],
//			self::OR_WHERE => [],
//			self::WHERE_NOT_IN => [],
//			self::LIKE =>[],
//			self::ORDER_BY => [],
//			self::START => NULL,
//			self::LIMIT => NULL
//		];
		
		if(array_key_exists(self::SELECT, $queryArray)){
			$this->db->select($queryArray[self::SELECT]);
		}else{
			$this->db->select('*');
		}
		
		if(array_key_exists(self::FROM, $queryArray)){
			$this->db->from($queryArray[self::FROM]);
		}
        
		if(array_key_exists(self::JOIN, $queryArray)){
			foreach ($queryArray[self::JOIN] as $key => $value) {
				$this->db->join($key, $value);
			}
		}
		
		if(array_key_exists(self::LEFT_JOIN, $queryArray)){
			foreach ($queryArray[self::LEFT_JOIN] as $key => $value) {
				$this->db->join($key, $value, 'LEFT');
			}
		}
		
		if(array_key_exists(self::WHERE, $queryArray)){
			foreach ($queryArray[self::WHERE] as $key => $value) {
				if ($value !== NULL) {
					$this->db->where($key, $value);
				} else {
					$this->db->where($key, $value, FALSE);
				}
			}
		}
        
		if(array_key_exists(self::OR_WHERE, $queryArray)){
			foreach ($queryArray[self::OR_WHERE] as $key => $value) {
				if ($value !== NULL) {
					$this->db->or_where($key, $value);
				} else {
					$this->db->or_where($key, $value, FALSE);
				}
			}
		}
		
		if(array_key_exists(self::WHERE_NOT_IN, $queryArray)){
			foreach ($queryArray[self::WHERE_NOT_IN] as $key => $value) {
				$this->db->where_not_in($key, $value);
			}
		}
		
		if(array_key_exists(self::LIKE, $queryArray)){
			foreach ($queryArray[self::LIKE] as $key => $value) {
				$this->db->like($key, $value);
			}
		}
		
		if(array_key_exists(self::ORDER_BY, $queryArray)){ 
			foreach ($queryArray[self::ORDER_BY] as $key => $value) {
				$this->db->order_by($key, $value);
			}
		}
		
		$limit = array_key_exists(self::LIMIT, $queryArray) ? $queryArray[self::LIMIT] : NULL;
		$start = array_key_exists(self::START, $queryArray) ? $queryArray[self::START] : NULL;
        if (!is_null($limit) && !is_null($start)) {
            $this->db->limit($limit, $start);
        }

        $query = $this->db->get();
        return $query->result();
    }

    private function _count($queryArray) {
        $queryArray[self::SELECT] = 'COUNT('.$queryArray[self::SELECT].') as my_count';
		
		$results = $this->_select($queryArray);
        return $results[0]->my_count;
    }

    private function _sum($queryArray) {
        $queryArray[self::SELECT] = 'SUM('.$queryArray[self::SELECT].') as my_count';
		
		$results = $this->_select($queryArray);
        return $results[0]->my_count;
    }

    private function _insert($data_insert, $table) {
        $is_inserito = $this->db->insert($table, $data_insert);
        if ($is_inserito) {
            return $this->db->insert_id();
        }
        return $is_inserito;
    }

    private function _update($where, $data_to_update, $table) {
        foreach ($where as $key => $value) {
            $this->db->where($key, $value);
        }
        $is_aggiornato = $this->db->update($table, $data_to_update);
        if ($is_aggiornato) {
            return $this->db->affected_rows();
        }
        return $is_aggiornato;
    }

    private function _delete($where, $table) {
        foreach ($where as $key => $value) {
            $this->db->where($key, $value);
        }
        $is_cancellato = $this->db->delete($table);
        return $is_cancellato;
    }

    public function _get_last_error() {
        return array(
            'err_code' => $this->db->_error_number(),
            'err_msg' => $this->db->_error_message()
        );
    }

}
