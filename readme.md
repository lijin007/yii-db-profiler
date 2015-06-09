forked from samdark/yii-db-profiler
===========
本来打算自己写,结果发现已经有了. 那么就偷懒了,在其基础上实现真实sql语句输出. 原来是参数化的,不方便调试查看
感谢原作者

![image](https://raw.githubusercontent.com/lijin007/yii-db-profiler/master/example1.jpg)

DB profiler
------------

Instead of regular `CProfileLogRoute` DB profiler displays database queries and
query-related info only. Also it have an ability to highligt possibly slow queries
and queries repeated many times.

Installation
------------

Unpack to `protected/extensions/`. Add the following to your `protected/config/main.php`:

~~~
<?php
return array(
	// …
	'components' => array(
		// …
		'db' => array(
			// …
			'enableProfiling'=>true,
			'enableParamLogging' => true,
		),
		'log'=>array(
			'class'=>'CLogRouter',
			'routes'=>array(
					// …
            	    array(
                	    'class'=>'ext.db_profiler.DbProfileLogRoute',
						'countLimit' => 1, // How many times the same query should be executed to be considered inefficient
						'slowQueryMin' => 0.01, // Minimum time for the query to be slow
                	),
			),
		),
	),
);
~~~