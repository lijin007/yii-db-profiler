<?php list($queryCount, $queryTime) = $app->db->getStats() ?>
<!-- start profiling summary -->
<table class="yiiLog" width="100%" cellpadding="2" style="border-spacing:1px;font:12px '宋体', Arial, Helvetica, sans-serif;background:#EEEEEE;color:#666666;">
	<tr>
		<th style="background:black;color:white;" colspan="6">
			DB Profiling Summary Report
			(Query count: <?php echo $queryCount?>, Total query time: <?php echo sprintf('%0.5f',$queryTime)?>s)
		</th>
	</tr>
	<tr style="background-color: #ccc;">
	    <th>Procedure</th>
		<th style="width:50px;">Count</th>
		<th style="width:80px;">Total (s)</th>
		<th style="width:80px;">Avg. (s)</th>
		<th style="width:80px;">Min. (s)</th>
		<th style="width:80px;">Max. (s)</th>
	</tr>
<?php
foreach($data as $index=>$entry)
{
	$color=($index%2)?'#F5F5F5':'#FFFFFF';
	$proc=CHtml::encode($entry[0]);
	$min=sprintf('%0.5f',$entry[2]);
	$max=sprintf('%0.5f',$entry[3]);
	$total=sprintf('%0.5f',$entry[4]);
	$average=sprintf('%0.5f',$entry[4]/$entry[1]);

	if($max>$slowQueryMin || $entry[1]>$countLimit)
	{
		$color = '#FFEEEE';
	}

	echo <<<EOD
	<tr style="background:{$color}">
		<td style="padding:3px 5px;background:{$color}">{$proc}</td>
		<td style="padding:3px 5px;background:{$color};">{$entry[1]}</td>
		<td style="padding:3px 5px;background:{$color};">{$total}</td>
		<td style="padding:3px 5px;background:{$color};">{$average}</td>
		<td style="padding:3px 5px;background:{$color};">{$min}</td>
		<td style="padding:3px 5px;background:{$color};">{$max}</td>
	</tr>
EOD;
}
?>
</table>
<br />
<br />
<!-- end of profiling summary -->