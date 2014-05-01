<?php

/**
 * This is the model class for table "socialmediamessages".
 *
 * The followings are the available columns in table 'socialmediamessages':
 * @property integer $recordId
 * @property integer $networkId
 * @property string $messageBody
 * @property integer $messageId
 * @property string $extraParams
 * @property integer $userId
 * @property integer $messageFrom
 * @property integer $messageDate
 * @property integer $parentId - represent the status of the message (0-it is real post to wall, 1-it is a comment to post (has a parent))
 * @property integer $avatar
 * @property integer $messageFromId
 */
class SocialMediaMessages extends SocialActiveRecord {

    /**
     * Returns the static model of the specified AR class.
     * @return Socialmediamessages the static model class
     */
    public static function model($className=__CLASS__) {
        return parent::model($className);
    }

    /**
     * @return string the associated database table name
     */
    public function tableName() {
        return 'socialmediamessages';
    }

    /**
     * @return array validation rules for model attributes.
     */
    public function rules() {
        // NOTE: you should only define rules for those attributes that
        // will receive user inputs.
        return array(
            array('networkId, messageBody, messageId, userId', 'required'),
            array('networkId, userId', 'numerical', 'integerOnly' => true),
            // The following rule is used by search().
            // Please remove those attributes that should not be searched.
            array('recordId, networkId, messageBody, messageId, extraParams, userId, messageFrom, messageDate, avatar,messageFromId', 'safe', 'on' => 'search'),
        );
    }

    /**
     * @return array relational rules.
     */
    public function relations() {
        // NOTE: you may need to adjust the relation name and the related
        // class name for the relations automatically generated below.
        return array(
            'mediainstance' => array(self::BELONGS_TO, 'SocialMedia', 'networkId'), // socialmedia account 
            'subordinatemessages' => array(self::HAS_MANY, 'socialmediamessages', 'parentId'), // filter by parentId
        );
    }

    /**
     * @return array customized attribute labels (name=>label)
     */
    public function attributeLabels() {
        return array(
            'recordId' => 'Record',
            'networkId' => 'Network',
            'messageBody' => 'Message Body',
            'messageId' => 'Message',
            'extraParams' => 'Extra Params',
            'userId' => 'User',
        );
    }

    /**
     * Retrieves a list of models based on the current search/filter conditions.
     * @return CActiveDataProvider the data provider that can return the models based on the search/filter conditions.
     */
    public function search() {
        // Warning: Please modify the following code to remove attributes that
        // should not be searched.
        $sort = new CSort();
        $sort->attributes = array(
            'network' => array(
                'asc' => 'networkId',
                'desc' => 'networkId desc',
            ),
        );

        $criteria = new CDbCriteria;

        $criteria->compare('recordId', $this->recordId);
        $criteria->compare('networkId', $this->networkId);
        $criteria->compare('messageBody', $this->messageBody, true);
        $criteria->compare('messageId', $this->messageId);
        $criteria->compare('extraParams', $this->extraParams, true);
        $criteria->compare('userId', $this->userId);
        $criteria->compare('messageFrom', $this->messageFrom);
        $criteria->compare('messageDate', $this->messageDate);
        $criteria->compare('avatar', $this->avatar);
        $criteria->compare('parentId', $this->parentId);
        $criteria->compare('messageFromId', $this->messageFromId);
        return new CActiveDataProvider(get_class($this), array(
            'criteria' => $criteria,
            'sort' => $sort,
        ));
    }

    /**
     * Default Yii scope
     * Checks the user access rights (userId scope) 
     * @return <array>
     */
    public function defaultScope() {

        if (Yii::app()->params['endName'] != 'console') {
            return array(
                'condition' => "userId='" . Yii::app()->getUser()->id . "'",
            );
        }
        else
            return array();
    }

    public function beforeSave() {
        parent::beforeSave();
        $this->userId = Yii::app()->getUser()->id;
        return true;
    }

    public function beforeValidate() {
        parent::beforeValidate();
        $this->userId = Yii::app()->getUser()->id;
        return true;
    }

    public function deleteData() {
        parent::deleteData(array('userId' => Yii::app()->getUser()->id));
        return true;
    }

    public function getNetworkTypeFieldValue() {
        return $this->networkId;
    }

    public function getSocialNetworkUserID() {
        return $this->messageFromId;
    }

}
