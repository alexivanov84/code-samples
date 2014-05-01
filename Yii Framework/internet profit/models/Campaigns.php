<?php

/**
 * This is the model class for table "campaigns".
 *
 * The followings are the available columns in table 'campaigns':
 * @property string $mktgID
 * @property string $mktgCreated
 * @property string $mktgStart
 * @property string $mktgEnd
 * @property integer $mktgOwner
 * @property string $mktgName
 * @property string $mktgType
 * @property string $mktgSummary
 * @property string $mktgChannel
 * @property double $mktgCost
 * @property string $mktgClientID
 *
 * The followings are the available model relations:
 * @property CampaignRequests[] $campaignRequests
 * @property Marketingchannels $mktgChannel
 */
class Campaigns extends CActiveRecord {

    public static function model($className = __CLASS__) {
        return parent::model($className);
    }

    public function tableName() {
        return 'campaigns';
    }

    public function rules() {
        return array(
            array('mktgStart, mktgEnd, mktgName, mktgSummary, mktgChannel, mktgCost, mktgProfit', 'required'),
            array('mktgChannel', 'numerical', 'integerOnly' => true, 'allowEmpty' => false, 'min' => 1, 'tooSmall' => 'Select channel'),
            array('mktgCost, mktgProfit', 'numerical'),
            array('mktgName', 'length', 'max' => 45),
            array('mktgClientID', 'safe'),
            array('mktgCostEnabled', 'boolean'),
            array('mktgID, mktgCreated, mktgStart, mktgEnd, mktgOwner, mktgName, mktgSummary, mktgChannel, mktgCost, mktgClientID', 'safe', 'on' => 'search'),
        );
    }

    public function relations() {
        return array(
            'campaignRequests' => array(self::HAS_MANY, 'CampaignRequests', 'campaing_id'),
            'channel' => array(self::BELONGS_TO, 'MarketingChannels', 'mktgChannel'),
            'stats' => array(self::HAS_ONE, 'CampaignRequests', 'campaign_id', 'select' => '
                                                                                            SUM(traffic) AS traffic,
                                                                                            SUM(hit) AS hit,
                                                                                            SUM(revenue) AS revenue,
                            '),
            'trafficSum' => array(self::STAT, 'CampaignRequests', 'campaign_id', 'select' => 'SUM(traffic)'),
            'hitSum' => array(self::STAT, 'CampaignRequests', 'campaign_id', 'select' => 'SUM(hit)'),
            'revenueSum' => array(self::STAT, 'CampaignRequests', 'campaign_id', 'select' => 'SUM(revenue)'),
            'statistic' => array(self::HAS_ONE, 'NewsletterStatistic', 'campaignId'),
        );
    }

    public function attributeLabels() {
        return array(
            'mktgID' => 'ID',
            'mktgCreated' => 'Created',
            'mktgStart' => 'Start date',
            'mktgEnd' => 'End date',
            'mktgOwner' => 'Owner',
            'mktgName' => 'Name',
            'mktgSummary' => 'Summary',
            'mktgChannel' => 'Channel',
            'mktgCost' => 'Campaign Cost',
            'mktgClientID' => 'Client ID',
            'mktgCostEnabled' => 'Campaign Type',
            'mktgProfit' => 'Avg. Profit ' . Yii::app()->config->get('currency_code') . '/Profit Markup %',
            'trafficSum' => 'Traffic',
            'hitSum' => 'Hits',
            'revenueSum' => 'Revenue Sum',
            'revenueAvg' => 'Average Profit per Sale',
            'conversionRate' => 'Conversion Rate',
            'costPerConversion' => 'Cost Per Conversion',
            'ROI' => 'ROI',
        );
    }

    public function getAuthorizeKey() {
        return substr(md5($this->mktgID . $this->mktgClientID . $this->mktgOwner . MHA_SALT), 0, 20);
    }

    public function getTrafficCode() {
        return MHA_SITE_URL . "/api/campaignsAPI/?id=" . $this->mktgID . "&ak=" . $this->authorizeKey . "&type=traffic";
    }

    public function getLeadsCode() {
        return MHA_SITE_URL . "/api/campaignsAPI/?id=" . $this->mktgID . "&ak=" . $this->authorizeKey . "&type=leads";
    }

    public function getSalesCode() {
        return MHA_SITE_URL . "/api/campaignsAPI/?id=" . $this->mktgID . "&ak=" . $this->authorizeKey . "&type=sales&revenue=%REVENUE_SUM%";
    }

    public function search() {
        $criteria = new CDbCriteria;

        $criteria->compare('mktgID', $this->mktgID, true);
        $criteria->compare('mktgCreated', $this->mktgCreated, true);
        $criteria->compare('mktgStart', $this->mktgStart, true);
        $criteria->compare('mktgEnd', $this->mktgEnd, true);
        $criteria->compare('mktgOwner', $this->mktgOwner);
        $criteria->compare('mktgName', $this->mktgName, true);
        $criteria->compare('mktgType', $this->mktgType, true);
        $criteria->compare('mktgSummary', $this->mktgSummary, true);
        $criteria->compare('mktgChannel', $this->mktgChannel, true);
        $criteria->compare('mktgCost', $this->mktgCost);
        $criteria->compare('mktgClientID', $this->mktgClientID, true);

        return new CActiveDataProvider(get_class($this), array(
                    'criteria' => $criteria,
                ));
    }

    public function defaultScope() {
        if (Yii::app()->params['endName'] != 'console') {
            return array(
                'condition' => "mktgOwner='" . Yii::app()->getUser()->id . "'",
            );
        }
        else
            return array();
    }

    public function beforeValidate() {
        parent::beforeValidate();
        $this->mktgOwner = Yii::app()->getUser()->id;
        return true;
    }

    public function afterConstruct() {
        return true;
    }

    public function beforeSave() {
        if ($this->isNewRecord && empty($this->mktgCreated)) {
            $this->mktgCreated = new CDbExpression("NOW()");
            $this->mktgOwner = Yii::app()->user->id;
        }

        return parent::beforeSave();
    }

    public function beforeDelete() {
        parent::beforeDelete();
        if (!is_null($this->statistic)) {
            //statistic found=>delete it as long as campaign for it
            $newsId = $this->statistic->newsletterId;
            Newsletter::model()->deleteByPk($newsId); //byPK because it doesn't invoke beforeSave/afterSave. We do not need it in this case
            $this->statistic->delete();
        }
        return true;
    }

}
