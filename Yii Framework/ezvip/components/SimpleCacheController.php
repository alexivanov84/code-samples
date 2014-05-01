<?php

class SimpleCacheController extends CController
{

	protected $debugMode = 0;
	protected $cacheDuration = 120;
	protected $withoutCacheActions = "";
	protected $onlyCacheActions = ""; //if set onlyActions then minusActions not considered
	
	public function filters()
	{
	    $request = yii::app()->getRequest();
		$url = $request->getUrl();
		$no_cache = $request->getQuery('no_cache', '');
	    $location = isset($_SESSION['region']) ? $_SESSION['region'] : "";
	    
	    $cacheFilter = 'COutputCache';
	    if(strlen($this->onlyCacheActions)){
	    	$cacheFilter .= " + {$this->onlyCacheActions}";
	    } else if(strlen($this->withoutCacheActions)){
	    	$cacheFilter .= " - {$this->withoutCacheActions}";
	    }
	    
	    //error_log("duration = '{$this->cacheDuration}' varyByExpression = '$location.$url'");
	    if(!($this->debugMode || $no_cache)){
			return array(
		        array(
		            $cacheFilter,
		            'duration'=>$this->cacheDuration,
		        	'varyByRoute'=>1,
		            'varyByExpression'=>"'$location.$url'",
		        ),
		    );
	    } else {
	    	return array();
	    }
	}	
	
}