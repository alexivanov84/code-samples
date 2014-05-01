<?php


/**

 * This is the model class for table "places".

 *

 * The followings are the available columns in table 'places':

 * @property integer $id
 * @property integer $blitz_id
 * @property string $place
 * @property string $address
 * @property string $city
 * @property string $zip
 * @property string $description
 * @property integer $review
 * @property string $Latitude
 * @property string $Longitude
 * @property string $photos
 * @property string $cdate
 * @property string $mdate
 */

class Places extends CActiveRecord
{
	/**

	 * Returns the static model of the specified AR class.

	 * @return Places the static model class

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

		return 'places';

	}



	/**

	 * @return array validation rules for model attributes.

	 */

	public function rules()

	{

		// NOTE: you should only define rules for those attributes that

		// will receive user inputs.

		return array(

			array('place, address, description, review, Latitude, Longitude', 'required'),
			array('blitz_id, review', 'numerical', 'integerOnly'=>true),
			array('place, address', 'length', 'max'=>50),
			array('city', 'length', 'max'=>20),
			array('zip', 'length', 'max'=>15),
                        array('Latitude', 'length', 'max'=>10),
			array('Longitude', 'length', 'max'=>11),
                    
			array('blitz_id', 'unsafe'),
			array('cdate, mdate', 'unsafe'),
			// The following rule is used by search().

			// Please remove those attributes that should not be searched.

			array('id, blitz_id, place, address, city, zip, description, review, Latitude, Longitude', 'safe', 'on'=>'search'),

		);

	}

    
    public function scopes() {
        return array(
            'recently'=>array(
                //'order'=>'cdate DESC',
                //temporary solution, while cdate is empty for most records
                'order'=>'id DESC',
                'limit'=>3,
            ),
            'topRated'=>array(
                'order'=>'review DESC',
                'limit'=>3,
            ),
        );
    }
    
    public function beforeValidate(){
        if(isset($this->Latitude)) {
            $this->Latitude = round($this->Latitude, 6);
        }
        if(isset($this->Longitude)) {
            $this->Longitude = round($this->Longitude, 6);
        }
        return true;
    }

    /**
     *
     * @return array model behaviors
     */
    public function behaviors() {
        return array(
            'TimestampBehavior' => array(
                'class' => 'application.components.behaviors.TimestampBehavior',
            ),
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
			'photos' => array(self::HAS_MANY, 'PlacesPhoto', 'place_id'),
			'blitz' => array(self::BELONGS_TO, 'Blitz', 'blitz_id'),
			'cityRel' => array(self::BELONGS_TO, 'Cities', 'city'),
		);

	}



	/**

	 * @return array customized attribute labels (name=>label)

	 */

	public function attributeLabels()

	{

		return array(
                        
			'id' => 'ID',
			'blitz_id' => 'Blitz',
			'place' => 'Location Name',
			'address' => 'Address',
			'city' => 'City',
			'zip' => 'Zip',
			'description' => 'Description',
			'review' => 'Review',
			'photos' => 'Photos',
                        'latitude' => 'Latitude',
                        'longitude' => 'Longitude'
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
		
		$criteria->compare('id',$this->id);
		$criteria->compare('blitz_id',$this->blitz_id);
		$criteria->compare('place',$this->place,true);
		$criteria->compare('address',$this->address,true);
		$criteria->compare('city',$this->city,true);
		$criteria->compare('zip',$this->zip,true);
                $criteria->compare('Latitude',$this->Latitude,true);
                $criteria->compare('Longitude',$this->Longitude,true);
		//$criteria->compare('arrival',$this->arrival,true);
		//$criteria->compare('departure',$this->departure,true);
		$criteria->compare('description',$this->description,true);
		$criteria->compare('review',$this->review);


		return new CActiveDataProvider(get_class($this), array(

			'criteria'=>$criteria,

		));

	}
	

    public function savePhotos() {
        $photos = Yii::app()->user->getState('xuploadFiles');
        $photosPath = Yii::getPathOfAlias('webroot') . DIRECTORY_SEPARATOR . Yii::app()->params['photosPath'] . DIRECTORY_SEPARATOR . Yii::app()->user->id;
        
        $photosFinalPath =  $photosPath . DIRECTORY_SEPARATOR . $this->id;
        $photosThumbsFinalPath =  $photosFinalPath . DIRECTORY_SEPARATOR . 'thumbs';

        //move to ccommonhelper
        if (!is_dir($photosFinalPath) && !mkdir($photosFinalPath, true)) {
                $this->addError('photos', 'Error creating directory "' . $photosFinalPath . '"');
                return false;
        }
		
        if (!chmod($photosFinalPath, 0777)) {
                $this->addError('photos', 'Error chmod directory "' . $photosFinalPath . '"');
                return false;
        }

        if (!is_dir($photosThumbsFinalPath) && !mkdir($photosThumbsFinalPath, true)) {
                $this->addError('photos', 'Error creating directory "' . $photosThumbsFinalPath . '"');
                return false;
        }

        if (!chmod($photosThumbsFinalPath, 0777)) {
                $this->addError('photos', 'Error chmod directory "' . $photosThumbsFinalPath . '"');
                return false;
        }
		
        $thumbsSize = Yii::app()->params['photosThumbsSize'];
        foreach ($photos as $photo) {
            $tmpPhotoPath = $photosPath . DIRECTORY_SEPARATOR . 'tmp' . DIRECTORY_SEPARATOR . $photo['path'] . DIRECTORY_SEPARATOR . $photo['file_name'];
            $fileExt = pathinfo($tmpPhotoPath, PATHINFO_EXTENSION);
            $newFileName = uniqid() . '.' . $fileExt;
            $photoFinalPath = $photosFinalPath . DIRECTORY_SEPARATOR . $newFileName;
            if (!rename ($tmpPhotoPath, $photoFinalPath)) {
                    //$this->addError('photos', 'Error rename photo from "' . $tmpPhotoPath . '" to "' . $photoFinalPath . '"');
                    //return false;
                    //TODO: add log message or send error to admin
                    continue;
            }
            CCommonHelper::createThumbsImage($photoFinalPath, $thumbsSize);

            $placePhoto = new PlacesPhoto();
            $placePhoto->place_id = $this->id;
            $placePhoto->file_name = $newFileName;
            $placePhoto->path = $photosFinalPath;
            $placePhoto->original_name = $photo['original_name'];
            if ($placePhoto->save()) {
                $placePhoto->attributes = null;
                //create thumbnails
                CCOmmonHelper::createThumbsImage($photoFinalPath, Yii::app()->phpThumb->options->size);
            } else {
                $errors = var_export($placePhoto->getErrors(), true);
                $this->addError('photos', 'Error saving photo:' . $errors);
                return false;
            }
        }
        Yii::app()->user->setState('xuploadFiles', null);
        return true;
    }
   
   public function getFirstPhoto() {
	   if (!empty($this->photos[0])) {
		   return $this->photos[0];
	   } else {
		   return new PlacesPhoto('fake');
	   }
   }
   
   public function afterDelete() {
            $photosPath = Yii::getPathOfAlias('webroot') . DIRECTORY_SEPARATOR . Yii::app()->params['photosPath'] . DIRECTORY_SEPARATOR . Yii::app()->user->id;
            $photosFinalPath =  $photosPath . DIRECTORY_SEPARATOR . $this->id;
            $photosThumbPath = $photosFinalPath.DIRECTORY_SEPARATOR. 'thumbs';
            $photos = $this->photos;
            $thumbsSize = Yii::app()->params['photosThumbsSize'];
            foreach($photos as $photo) {
                $photoFinalPath = $photosFinalPath . DIRECTORY_SEPARATOR . $photo->file_name;
                CCommonHelper::deleteThumbsImage($photoFinalPath, $thumbsSize);
                $photo->delete();
            }
            @unlink($photosThumbPath);
            @unlink($photosFinalPath);
            
            parent::afterDelete();
   }
   
}

