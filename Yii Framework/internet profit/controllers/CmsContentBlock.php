<?php
/**
 * This is the model class for table "cms_content_blocks".
 *
 * @author Sergii 'b3atb0x' <hello@webkadabra.com>
 * @package Cms
 *
 * The followings are the available columns in table 'cms_content_blocks':
 * @property string $contentID
 * @property string $contentBlock
 * @property string $content
 * @property string $Pages_pageKey
 * @property string $contentType
 *
 * The followings are the available model relations:
 * @property Pages $pagesPageKey
 */

class CmsContentBlock extends CActiveRecord
{
	/**
	 * Returns the static model of the specified AR class.
	 * @return CmsContentBlock the static model class
	 */
	public $id;
	public static function model($className=__CLASS__)
	{
		return parent::model($className);
	}

	/**
	 * @return string the associated database table name
	 */
	public function tableName()
	{
		return 'cms_content_blocks';
	}

	/**
	 * @return array validation rules for model attributes.
	 */
	public function rules()
	{
		// NOTE: you should only define rules for those attributes that
		// will receive user inputs.
		return array(
			array('content', 'safe'),
			array('contentBlockName', 'length', 'max'=>100),
			array('contentType', 'length', 'max'=>10),
			// The following rule is used by search().
			// Please remove those attributes that should not be searched.
			array('contentBlockName, content, Pages_pageID, contentType', 'safe', 'on'=>'search'),
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
			'container' => array(self::BELONGS_TO, 'CmsNodeContainer', 'Pages_pageID')
		);
	}

	/**
	 * @return array customized attribute labels (name=>label)
	 */
	public function attributeLabels()
	{
		return array(
			'contentID' => 'ID',
			'contentBlockName' => 'Block name key',
			'content' => 'Content',
			'Pages_pageKey' => 'Page',
			'contentType' => 'Type',
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

		$criteria->compare('contentID',$this->contentID,true);
		$criteria->compare('contentBlock',$this->contentBlock,true);
		$criteria->compare('content',$this->content,true);
		$criteria->compare('Pages_pageKey',$this->Pages_pageKey,true);
		$criteria->compare('contentType',$this->contentType,true);

		return new CActiveDataProvider(get_class($this), array(
			'criteria'=>$criteria,
		));
	}
}
