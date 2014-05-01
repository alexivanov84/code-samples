<?php
namespace Application\Model;

use Zend\Validator\Barcode\Ean12;

use Zend\Db\Adapter\Adapter;

use Zend\Db\TableGateway\TableGateway;
use Zend\Db\Sql\Sql;
use Zend\Db\Sql\Select;
use Zend\Db\Sql\Expression;
use Zend\Db\Adapter\Driver\ResultInterface;
use Zend\Db\ResultSet\ResultSet;

class StylebookTable
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
    
    public function getSelectOptions($user_id)
    {
        $user_id  = (int) $user_id;
        $options = array();
        $resultSet = $this->tableGateway->select(array('user_id'=>$user_id));
        
        if(!empty($resultSet)) {
            foreach($resultSet as $value) {
                $options[$value->id] = $value->title;
            }
        }
        
        return $options;
    }

    public function getStylebook($id)
    {
        $id  = (int) $id;
        $sql = new Sql($this->tableGateway->getAdapter());
        $select = $sql->select();
        
        $subquery = $sql->select()
    		->from(array('m1'=>'media'))
    		->columns(array('mid'=>new Expression('MAX(m1.id)')))
    		->join(array('ms1'=>'media_stylebook'), 'm1.id=ms1.media_id', array('sid'=>'stylebook_id'))
    		->where('stylebook_id='.$id);
        
        $select->from(array('s' => 'stylebook'))
        ->join(array('st' => 'stylebook_tag'), 's.id = st.stylebook_id', array())
        ->join(array('t'=>'tag'), 'st.tag_id = t.id', array('tname'=>'name', 'tid'=>'id'))
        ->join(array('tt'=>'tag_type'), 't.tag_type_id=tt.id', array())
        ->join(array('u'=>'user'), 's.user_id=u.id', array('username'=>'username', 'uid'=>'id'))
        ->join(array('sq'=>$subquery), 's.id=sq.sid', array('mid'=>'sq.mid'), 'left')
        ->where(array('tt.type="season"', 's.id=?'));
        
        $statement = $sql->prepareStatementForSqlObject($select);
        $results = $statement->execute(array(':where1'=>$id));
        $row = $results->current();
        
        if(isset($row['mid'])) {
            $select = $sql->select()
                    ->from(array('m'=>'media'))
                    ->columns(array('added_on'=>'added_on'))
                    ->where('id=?');
            $statement = $sql->prepareStatementForSqlObject($select);
            $results = $statement->execute(array(':where1'=>$row['mid']));

            $media = $results->current();
            $row['mdate'] = $media['added_on'];
        }
        
        return $row;
    }
    
    public function getUserStylebookSeasons($userid) {
    	$userid = (int) $userid;
    	$sql = new Sql($this->tableGateway->getAdapter());
    	$select = $sql->select();
    	$select->from(array('s' => 'stylebook'))->columns(array())
    		->join(array('st' => 'stylebook_tag'), 's.id = st.stylebook_id', array())
    		->join(array('t'=>'tag'), 'st.tag_id = t.id', array('tname'=>new Expression('DISTINCT(name)'), 'tid'=>'id'))
    		->join(array('tt'=>'tag_type'), 't.tag_type_id=tt.id', array())
    		->where(array('tt.type="season"', 's.user_id=?'))
    		->order('t.added_on DESC');
    	
    	$statement = $sql->prepareStatementForSqlObject($select);
    	$results = $statement->execute(array(':where1'=>$userid));

    	$resultSet = new ResultSet();
    	$resultSet->initialize($results);
    	return $resultSet;
    }
    
    public function getUserStylebooksBySeason($userid, $season_id){
    	$sql = new Sql($this->tableGateway->getAdapter());
    	$select = $sql->select();
    	
    	$userid  = (int) $userid;
    	$season_id = (int) $season_id;

    	$select->from(array('s' => 'stylebook'))
    	->columns(array('sid'=>'id', 'title'=>'title', 'description'=>'description', 'likes'=>'likes', 'comments_count'=>'comments_count'))
    	->join(array('st' => 'stylebook_tag'), 's.id = st.stylebook_id')
    	->join(array('t'=>'tag'), 'st.tag_id = t.id')
    	->join(array('tt'=>'tag_type'), 't.tag_type_id=tt.id')
    	->join(array('ms'=>'media_stylebook'), 's.id=ms.stylebook_id', array('media_count'=>new Expression('COUNT(ms.id)')), $select::JOIN_LEFT)
    	->where(array('tt.type="season"','s.user_id=?', 't.id=?'))
    	->order('t.added_on DESC, s.added_on DESC')
    	->group('s.id');
    	
    	$statement = $sql->prepareStatementForSqlObject($select);
    	$results = $statement->execute(array(':where1'=>$userid, ':where2'=>$season_id));
    	$resultSet = new ResultSet();
    	$resultSet->initialize($results);
    	return $resultSet;
    	
    }
    
    public function getStylebooksThumbnails($ids) {
    	foreach($ids as $id) {
    		$stylebook_ids[]= (int) $id;
    	}

    	$sql = new Sql($this->tableGateway->getAdapter());
    	$select = $sql->select();
    	$subquery = $sql->select()
    		->from(array('m1'=>'media'))
    		->columns(array('mid'=>new Expression('MAX(m1.id)')))
    		->join(array('ms1'=>'media_stylebook'), 'm1.id=ms1.media_id', array('sid'=>'stylebook_id'))
    		->where('stylebook_id IN ('.implode(',',$stylebook_ids).')')
    		->group('stylebook_id');
    	
    	$select->from(array('m'=>'media'))
    		->columns(array('title',  'mid'=>'id', 'added_on'))
    		->join(array('ms'=>'media_stylebook'), 'm.id=ms.media_id', array('sid'=>'stylebook_id'))
    		->join(array('sq'=>$subquery), 'ms.stylebook_id=sq.sid AND m.id=sq.mid');
    
    	$statement = $sql->prepareStatementForSqlObject($select);
    	$results = $statement->execute();
    	$resultSet = new ResultSet();
    	$resultSet->initialize($results);
    	return $resultSet;
    }
    
    public function getTopStylebooks($limit) {
    	$sql = new Sql($this->tableGateway->getAdapter());
    	$select = $sql->select();
    	$select->from(array('s' => 'stylebook'))
    	->columns(array('sid'=>'id', 'title'=>'title', 'description'=>'description', 'likes'=>'likes', 'comments_count'=>'comments_count'))
    	->join(array('ms'=>'media_stylebook'), 's.id=ms.stylebook_id', array('media_count'=>new Expression('COUNT(ms.id)')))
    	//->join(array('m'=>'media'), 'ms.media_id=m.id', array('media_likes'=>new Expression('SUM(m.likes)')))
    	->group('s.id')
    	->order('s.likes DESC')
    	->limit($limit);
    	 
    	$statement = $sql->prepareStatementForSqlObject($select);
    	$results = $statement->execute();
    	$resultSet = new ResultSet();
    	$resultSet->initialize($results);
    	return $resultSet;
    }
    
    
    public function addStylebook(Stylebook $stylebook)
    {
    	$data = array(
    			'user_id'	=> $stylebook->user_id,
    			'title' => $stylebook->title,
    			'description' => $stylebook->description,
    	);
    	
    	$this->tableGateway->insert($data);
    	
        return $this->tableGateway->getLastInsertValue(); 
    }
    
    public function editStylebook(Stylebook $stylebook) {
    	foreach ($stylebook as $field=>$value) {
    		if ($value) $data[$field]=$value;
    	}
    	if ($this->getStylebook($stylebook->id)) {
    		$this->tableGateway->update($data, array('id' => $stylebook->id));
    	} else {
    		throw new \Exception('Stylebook id does not exist');
    	}
    }
    
    public function deleteStylebook($id)
    {
        return $this->tableGateway->delete(array('id' => $id));
        
    }
    
}