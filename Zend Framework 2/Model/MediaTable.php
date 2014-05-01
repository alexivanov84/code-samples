<?php
namespace Application\Model;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
//use Zend\Db\Sql\Where;
use Zend\Db\Sql\Expression;
use Zend\Db\ResultSet\ResultSet;
use Zend\Paginator\Adapter\DbSelect;
use Zend\Paginator\Paginator;


class MediaTable
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
    
    public function getStylebookMedia($stylebook_id, $items_count = null, $page_number = null, $user_id = 0) {
    	$stylebook_id = (int) $stylebook_id;
        $user_id = (int) $user_id;
    	$sql = new Sql($this->tableGateway->getAdapter());
    	$select = $sql->select();
    	
    	$query = $select->from(array('m'=>'media'))
    			->join(array('ms'=>'media_stylebook'), 'm.id=ms.media_id', array('msid'=>'id'))
    			->where('ms.stylebook_id= ?')
                        ->order('m.added_on DESC')
                        ;
        
        $statement_array = array(':where1'=>$stylebook_id);
        
        if($user_id) {
            $query = $query->where('user_id= ?');
            $statement_array[':where2'] = $user_id;
        }
        
    	if($items_count != null && $page_number != null) {
           $query = $query->limit($items_count)
                            ->offset(($page_number - 1) * $items_count);
        }
        
    	$statement = $sql->prepareStatementForSqlObject($select);
    	$results = $statement->execute($statement_array);
    	$resultSet = new ResultSet();
    	$resultSet->initialize($results);
    	return $resultSet;
    }
    
    public function getStylebookMediaCount($stylebook_id) {
    	$stylebook_id = (int) $stylebook_id;
    	$sql = new Sql($this->tableGateway->getAdapter());
    	$select = $sql->select();
    	$select->from(array('m'=>'media'))
    		->columns(array('mcount'=>new Expression('COUNT(m.id)')))
	    	->join(array('ms'=>'media_stylebook'), 'm.id=ms.media_id', array())
	    	->join(array('mt'=>'media_type'), 'm.media_type_id=mt.id')
	    	->where(array('ms.stylebook_id=?'))
	    	->group('m.media_type_id');
    	$statement = $sql->prepareStatementForSqlObject($select);
    	$results = $statement->execute(array(':where1'=>$stylebook_id));
    	$resultSet = new ResultSet();
    	$resultSet->initialize($results);
    	return $resultSet;
    }
    
    public function getMedia($id)
    {
        $id  = (int) $id;
        $sql = new Sql($this->tableGateway->getAdapter());
        $select = $sql->select();
        
        $select->from(array('m'=>'media'))
    		->join(array('u'=>'user'), 'm.user_id=u.id', array('username'=>'username', 'uid'=>'id', 'fname'=>'first_name', 'lname'=>'last_name'))
    		->where('m.id=?');
        
        $statement = $sql->prepareStatementForSqlObject($select);
        $results = $statement->execute(array(':where1'=>$id));
        $row = $results->current();
        
        if (!$row) {
            throw new \Exception("Could not find row $id");
        }
        
        return (object) $row;
    }
    
    /*
    public function getFilteredMediaByTag($parameters, $limit, $offset){
    		
    	$sql = new Sql($this->tableGateway->getAdapter());
    	$select = $sql->select();
    	$select->from(array('m'=>'media'))
    	->columns(array('id', 'title', 'likes', 'comments_count', 'added_on'));
    	$i=0;
    	foreach($parameters as $param) {
    		$select->join(array('mt'.$i=>'media_tag'), 'm.id=mt'.$i.'.media_id', array())
    		->join(array('t'.$i=>'tag'), 'mt'.$i.'.tag_id=t'.$i.'.id', array())
    		->where(array('t'.$i.'.name=?'));
    		$where[':where'.($i+1)]=$parameters[$i];
    		$i++;
    	}
    	$select->limit($limit);
    	$select->offset($offset);
    	
    	$statement = $sql->prepareStatementForSqlObject($select);
    	$results = $statement->execute($where);
    	$resultSet = new ResultSet();
    	$resultSet->initialize($results);
    	return $resultSet;
    }
    */
    
    public function getFilteredMediaByTag($parameters, $items_count, $page_number){
    		
    	$sql = new Sql($this->tableGateway->getAdapter());
    	$select = $sql->select();
    	$select->from(array('m'=>'media'))
    	->columns(array('id', 'title', 'likes', 'comments_count', 'adds_count', 'added_on'))
    	->join(array('u'=>'user'), 'm.user_id=u.id', array())
    	->join(array('ut'=>'user_type'), 'u.user_type_id=ut.id', array('user_type'=>'type'));
    	$i=0;
    	foreach($parameters as $param) {
    		$select->join(array('mt'.$i=>'media_tag'), 'm.id=mt'.$i.'.media_id', array())
    		->join(array('t'.$i=>'tag'), 'mt'.$i.'.tag_id=t'.$i.'.id', array())
    		->where(array('t'.$i.'.name'=>$parameters[$i]));
    		$i++;
    	}
    	$select->order('m.added_on DESC');
    	$paginatorAdapter = new DbSelect($select, $this->tableGateway->getAdapter());
    	
    	$paginator = new Paginator($paginatorAdapter);
    	$paginator->setItemCountPerPage($items_count);
    	$paginator->setCurrentPageNumber($page_number);
        return $paginator;
    }
    
    public function getSearchedMedia($searchparam, $filterparam, $items_count, $page_number){
    	$sql = new Sql($this->tableGateway->getAdapter());
    	$select = $sql->select();
    	$select->from(array('m'=>'media'))
    	->columns(array('id', 'title', 'likes', 'comments_count', 'adds_count', 'added_on'))
    	->join(array('u'=>'user'), 'm.user_id=u.id', array())
    	->join(array('ut'=>'user_type'), 'u.user_type_id=ut.id', array('user_type'=>'type'));
    	$i=0;
    	foreach($filterparam as $param) {
    		$select->join(array('mt'.$i=>'media_tag'), 'm.id=mt'.$i.'.media_id', array())
    		->join(array('t'.$i=>'tag'), 'mt'.$i.'.tag_id=t'.$i.'.id', array())
    		->where(array('t'.$i.'.name'=>$filterparam[$i]));
    		$i++;
    	}
    	$i=0;
    	foreach($searchparam as $sp) {
    		$select->where->like('title','%'.$searchparam[$i++].'%');
    	}
    	$select->order('m.added_on DESC');
    	
  	
    	$paginatorAdapter = new DbSelect($select, $this->tableGateway->getAdapter());
    	 
    	$paginator = new Paginator($paginatorAdapter);
    	$paginator->setItemCountPerPage($items_count);
    	$paginator->setCurrentPageNumber($page_number);
    	return $paginator;
    	
    }
    
    public function getMostLikedMedia($limit) {
    	$sql = new Sql($this->tableGateway->getAdapter());
    	$select = $sql->select();
    	$select->from(array('m'=>'media'))
    	->columns(array('id', 'title', 'likes', 'comments_count', 'adds_count', 'added_on'))
    	->order('likes DESC')
    	->limit($limit);
    	$statement = $sql->prepareStatementForSqlObject($select);
    	$results = $statement->execute();
    	$resultSet = new ResultSet();
    	$resultSet->initialize($results);
    	return $resultSet;
    }

    public function getLatestMedia($limit) {
    	$sql = new Sql($this->tableGateway->getAdapter());
    	$select = $sql->select();
    	$select->from(array('m'=>'media'))
    	->columns(array('id', 'title', 'likes', 'comments_count', 'adds_count', 'added_on'))
    	->order('added_on DESC')
    	->limit($limit);
    	$statement = $sql->prepareStatementForSqlObject($select);
    	$results = $statement->execute();
    	$resultSet = new ResultSet();
    	$resultSet->initialize($results);
    	return $resultSet;
    }
    
    public function checkIfBelongsOtherStylebook($stylebook_id) {
    	$stylebook_id = (int) $stylebook_id;
    	$sql = new Sql($this->tableGateway->getAdapter());
    	$select = $sql->select();
    	
    	$select->from(array('m'=>'media'))
                        ->columns(array('mcount'=>new Expression('COUNT(m.id)')))
    			->join(array('ms'=>'media_stylebook'), 'm.id=ms.media_id', array())
    			->where('ms.stylebook_id!= ?');
    	
    	$statement = $sql->prepareStatementForSqlObject($select);
    	$results = $statement->execute(array(':where1'=>$stylebook_id));
        $row = $results->current();
        
        if($row && $row['mcount'] > 1) {
            return true;
        }
    	
        return false;
    }
    


    
    public function addMedia(Media $media)
    {
    	$data = array(
                        'user_id' => $media->user_id,
                        'media_type_id' => $media->media_type_id,
                        'title' => $media->title,
                        'comment' => $media->comment,
                        'comment_private' => $media->comment_private
    	);
    	
    	if($this->tableGateway->insert($data)) {
            return $this->tableGateway->getLastInsertValue(); 
        } else {
            throw new \Exception('There was an error during insert media.');
        }
    	
    }
    
    public function editMedia(Media $media) {
    	foreach ($media as $field=>$value) {
    		if ($value || ($value == null && is_string($value))) $data[$field]=$value;
    	}
    	if ($this->getMedia($media->id)) {
    		$this->tableGateway->update($data, array('id' => $media->id));
    	} else {
    		throw new \Exception('Media id does not exist');
    	}
    }
    
    public function deleteMedia($id)
    {
        $this->tableGateway->delete(array('id' => $id));
    }
    
}