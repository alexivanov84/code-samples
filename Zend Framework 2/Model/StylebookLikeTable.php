<?php
namespace Application\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Expression;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
use Zend\Db\ResultSet\ResultSet;

class StylebookLikeTable
{
    protected $tableGateway;

    public function __construct(TableGateway $tableGateway)
    {
        $this->tableGateway = $tableGateway;
    }

    public function fetchAll()
    {
        $resultSet = $this->tableGateway->select();
        return $resultSet;
    }
    
    public function checkStylebookLikeByUser($stylebook_id, $user_id)
    {
        $stylebook_id  = (int) $stylebook_id;
        $user_id = (int) $user_id;
        
        $rowset = $this->tableGateway->select(array('stylebook_id' => $stylebook_id, 'user_id'=>$user_id));
        $row = $rowset->current();
        
        if ($row) {
            return $row->id;
        }
        return false;
    }
    
    public function getStylebookIdsByUser($user_id)
    {
        $user_id  = (int) $user_id;
        $results = array();
        
        $sql = new Sql($this->tableGateway->getAdapter());
    	$select = $sql->select();
    	
    	$select->from(array('ml'=>'stylebook_like'))
                        ->columns(array('stylebook_id'))    
    			->where('ml.user_id= ?');
        
    	$statement = $sql->prepareStatementForSqlObject($select);
    	$stylebookIds = $statement->execute(array(':where1'=>$user_id));
        
        foreach($stylebookIds as $stylebookId){
            $results[] = $stylebookId['stylebook_id'];
        }
        
        return $results;
    }
    
    public function getStylebookLike($id)
    {
        $id  = (int) $id;
        $rowset = $this->tableGateway->select(array('id' => $id));
        $row = $rowset->current();
        
        if (!$row) {
            throw new \Exception("Could not find row $id");
        }
        return $row;
    }
    
    public function addStylebookLike(StylebookLike $stylebookLike)
    {
    	$data = array(
                        'user_id' => $stylebookLike->user_id,
                        'stylebook_id' => $stylebookLike->stylebook_id
    	);
    	
    	$this->tableGateway->insert($data);
    	
        return $this->tableGateway->getLastInsertValue(); 
    }
    
    public function editStylebookLike(StylebookLike $stylebookLike) {
    	foreach ($stylebookLike as $field=>$value) {
    		if ($value) $data[$field]=$value;
    	}
    	if ($this->getTag($stylebookLike->id)) {
    		$this->tableGateway->update($data, array('id' => $stylebookLike->id));
    	} else {
    		throw new \Exception('Stylebook id does not exist');
    	}
    }
    
    public function deleteStylebookLikes($stylebookid) {
        $stylebookid = (int) $stylebookid;
        $this->tableGateway->delete(array('stylebook_id' => $stylebookid));
    }
    
    public function deleteStylebookLike($id=0, $stylebook_id=0)
    {
        if($stylebook_id)
            $this->tableGateway->delete(array('stylebook_id' => $stylebook_id));
        elseif($id)
            $this->tableGateway->delete(array('id' => $id));
    }
    
}