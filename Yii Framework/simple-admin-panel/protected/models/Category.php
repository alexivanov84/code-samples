<?php

/**
 * This is the model class for table "category".
 *
 * The followings are the available columns in table 'category':
 * @property integer $id
 * @property string $name
 * @property integer $author
 *
 * The followings are the available model relations:
 * @property Article[] $articles
 * @property User $author0
 */
class Category extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return Category the static model class
	 */
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'category';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('author, name', 'required'),
			array('author', 'numerical', 'integerOnly'=>true),
			array('name', 'length', 'max'=>255),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, name, author', 'safe', 'on'=>'search'),
		);
	}

	/**
	 * @return array relational rules.
	 */
	public function relations()
	{
		// NOTE: you may need to adjust the relation name and the related
		// class name for the relations automatically generated below.
		return array(
			'articles' => array(self::HAS_MANY, 'Article', 'category'),
			'user' => array(self::BELONGS_TO, 'User', 'author'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'name' => 'Name',
			'author' => 'Author',
		);
	}

	/**
	 * Retrieves a list of models based on the current search/filter conditions.
	 * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
	 */
	public function search()
	{
		// Warning: Please modify the following code to remove attributes that
		// should not be searched.

		$criteria=new CDbCriteria;

		$criteria->compare('`t`.id',$this->id);
		$criteria->compare('name',$this->name,true);
		$criteria->together = true; 
        $criteria->with = array('user');
		$criteria->compare('user.username',$this->author);

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
	
	/**
	 * Retrieves a list of categories.
	 * @return array.
	 */
	public function getList()
	{
		$user = Yii::app()->user;
		$sql = "SELECT `id`,`name` FROM {$this->tableName()}";
		if($user->isEditor()) {
			$sql .= " WHERE `author`=:id";
			$command = Yii::app()->db->createCommand($sql);
			$command->bindParam(":id", $user->id, PDO::PARAM_INT);
		} else {
			$command = Yii::app()->db->createCommand($sql);
		}
		
		$result = $command->queryAll();
		
		return $result;
		
	} 

}