<?php
/**
 * Database profile web logger
 *
 * Displays database query related info only.
 * Highlights possible problems.
 *
 * @author Alexander Makarov, Sam Dark
 * @version 1.0
 */
class DbProfileLogRoute extends CProfileLogRoute
{
	/**
	 * @var int How many times the same query should be executed to be considered inefficient
	 */
	public $countLimit = 1;

	/**
	 * @var float Minimum time for the query to be slow
	 */
	public $slowQueryMin = 0.01;

	/**
	 * Displays the summary report of the profiling result.
	 * @param array $logs list of logs
	 */
	protected function displaySummary($logs)
	{
		$stack=array();
		foreach($logs as $log)
		{
			if($log[1]!==CLogger::LEVEL_PROFILE || substr($log[2], 0, strlen('system.db.CDbCommand')) !== 'system.db.CDbCommand')
				continue;

			$message=$log[0];
			if(!strncasecmp($message,'begin:',6))
			{
				$log[0]=substr($message,6);
				$stack[]=$log;
			}
			else if(!strncasecmp($message,'end:',4))
			{
				$token=substr($message,4);
				if(($last=array_pop($stack))!==null && $last[0]===$token)
				{
					$token=str_replace($log[2], '', $token);
					//$token = trim($token, '()');
					$token = preg_replace('/(^\()|(\)$)/','',$token);

					$delta=$log[3]-$last[3];
					if(!$this->groupByToken)
						$token=$log[2];
					if(isset($results[$token]))
						$results[$token]=$this->aggregateResult($results[$token],$delta);
					else
						$results[$token]=array($token,1,$delta,$delta,$delta);
				}
				else
					throw new CException(Yii::t('yii','CProfileLogRoute found a mismatching code block "{token}". Make sure the calls to Yii::beginProfile() and Yii::endProfile() be properly nested.',
						array('{token}'=>$token)));
			}
		}

		$now=microtime(true);
		while(($last=array_pop($stack))!==null)
		{
			$delta=$now-$last[3];
			$token=$this->groupByToken ? $last[0] : $last[2];
			if(isset($results[$token]))
				$results[$token]=$this->aggregateResult($results[$token],$delta);
			else
				$results[$token]=array($token,1,$delta,$delta,$delta);
		}

		$entries=array_values($results);
		$func=create_function('$a,$b','return $a[4]<$b[4]?1:0;');
		usort($entries,$func);
		$entries = $this->parseSql($entries);
		$this->render('profile-summary',$entries);
	}
	/**
	 * sql转换
	 */
	protected function parseSql($entries){
		$obj = new YiiLogSql();
		$newEntries = array();
		foreach($entries as $k=>$v){
			// 去除无用信息
			if( strpos($v[0],'SHOW FULL COLUMNS') !== false ||
				strpos($v[0],'SHOW CREATE TABLE') !== false
				){
				continue;
			}
			//$sql = "SELECT * FROM `fxb_hotline` `t` WHERE `name` like ? AND `mobile` like ? ORDER BY create_time DESC LIMIT 20. Bound with 0='%晶%', 1='%0057%'";
			$v[0] = $obj -> getByStr($v[0]);
			$newEntries[] = $v;
		}
		return $newEntries;
	}

	/**
	 * Renders the view.
	 * @param string $view the view name (file name without extension). The file is assumed to be located under framework/data/views.
	 * @param array $data data to be passed to the view
	 */
	protected function render($view,$data)
	{
		$countLimit = $this->countLimit;
		$slowQueryMin = $this->slowQueryMin;

		$app=Yii::app();
		$isAjax=$app->getRequest()->getIsAjaxRequest();

		if($this->showInFireBug)
		{
			if($isAjax && $this->ignoreAjaxInFireBug)
				return;
			$view.='-firebug';
		}
		else if(!($app instanceof CWebApplication) || $isAjax)
			return;

		include dirname(__FILE__).DIRECTORY_SEPARATOR.'views'.DIRECTORY_SEPARATOR.$view.'.php';
	}

	/**
	 * @param string $value the type of the profiling report to display.
	 * Property is read only since the only valid value is 'summary'.
	 */
	public function setReport($value)
	{
		throw new CException('DbProfileLogRoute.report is read-only.');
	}
}
/**
 * 将yii中的log,转换完整sql
 */
class YiiLogSql {
	private $params;
	private $i;
	// 将字符串解析
	public function getByStr($sql) {
		$arr = explode('Bound with', $sql);
		if ($arr[1]) {
			// 检查是位置替换还是参数替换
			if( $n = preg_match('/(:.*)=/', $arr[1] ) ) {
				$strs = explode(',', $arr[1]);
				$params = array();
				foreach ($strs as $str) {
					$nt = preg_match('/(:.*)=(.*)/', $str, $matches);
					if($nt){
						$params[$matches[1]] = $matches[2];
					}
					
				}
				$sql = $this -> getByParam($arr[0], $params);
				
			}else{
				$params = explode(',', $arr[1]);
				foreach ($params as $k => $param) {
					$params[$k] = preg_replace('/\d+=/', '', $param);
				}
				$sql = $this -> getByPos($arr[0], $params);
				
			}
			
			$sql = trim($sql, ' .');
		}
		return $sql;
	}

	// 将位置解析
	public function getByPos($sql, $params) {
		$this -> params = $params;
		$this -> i = 0;
		// 逐个？替换成对应位置的params的参数
		return preg_replace_callback('/(\?)/', array($this, 'reCall'), $sql);
	}

	public function reCall($match) {
		return $this -> params[$this -> i++];
	}

	public function getByParam($sql, $params) {
		$params = array_reverse($params, true);
		// 为了保证顺序
		foreach ($params as $search => $replace) {
			$sql = str_replace($search, $replace, $sql);
		}
		return $sql;
	}

}
