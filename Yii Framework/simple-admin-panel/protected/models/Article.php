<?php

/**
 * This is the model class for table "article".
 *
 * The followings are the available columns in table 'article':
 * @property integer $id
 * @property string $title
 * @property string $excerpt
 * @property string $description
 * @property integer $author
 * @property integer $category
 *
 * The followings are the available model relations:
 * @property User $author0
 * @property Category $category0
 */
class Article extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @param string $className active record class name.
	 * @return Article the static model class
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
		return 'article';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('author, category, title, excerpt, description', 'required'),
			array('author, category', 'numerical', 'integerOnly'=>true),
			array('title', 'length', 'max'=>255),
			array('excerpt, description', 'safe'),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('id, title, author, category', 'safe', 'on'=>'search'),
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
			'user' => array(self::BELONGS_TO, 'User', 'author'),
			'cat' => array(self::BELONGS_TO, 'Category', 'category'),
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'id' => 'ID',
			'title' => 'Title',
			'excerpt' => 'Excerpt',
			'description' => 'Description',
			'author' => 'Author',
			'category' => 'Category',
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
		$criteria->compare('title',$this->title,true);
		$criteria->together = true; 
        $criteria->with = array('user', 'cat');
        $criteria->compare('user.username',$this->author,true, "OR");
		$criteria->compare('cat.name',$this->category,true, "OR");

		return new CActiveDataProvider($this, array(
			'criteria'=>$criteria,
		));
	}
}