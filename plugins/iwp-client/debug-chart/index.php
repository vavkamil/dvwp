<?php
	include_once ("src/fusioncharts.php");
?>
<html>

   <head>
	<title>IWP DEBUG CHART</title>
	<script src="https://static.fusioncharts.com/code/latest/fusioncharts.js"></script>
   </head>
   <body>
<?php
	if (empty($_GET['historyID'])) {
		exit();
	}
	$iwp_multicall_hisID = $_GET['historyID'];
	$current_dir = dirname( dirname( dirname( dirname(__FILE__) ) ) ).'/infinitewp/backups';
	$memoryPeakLog	 = 'DE_clMemoryPeak.'.$iwp_multicall_hisID.'.txt';
	$memoryUsageLog  = 'DE_clMemoryUsage.'.$iwp_multicall_hisID.'.txt';
	$timeTakenLog 	 = 'DE_clTimeTaken.'.$iwp_multicall_hisID.'.txt';
	$cpuUsageLog	 = 'DE_clCPUUsage.'.$iwp_multicall_hisID.'.txt';
	new IWP_Debug_Chart('Memory Usage in Real Time', ' MB',  $current_dir . '/'.$memoryUsageLog, 'Memory Usage', 'chart-1');
	new IWP_Debug_Chart('Time Taken', ' Sec', $current_dir . '/'.$timeTakenLog, 'Time Taken', 'chart-2');
	new IWP_Debug_Chart('CPU Usage', ' ',  $current_dir . '/'.$cpuUsageLog, 'CPU Usage', 'chart-3');
	// new IWP_Debug_Chart('Memory Peak', ' MB',  $current_dir . '/'.$memoryPeakLog, 'Memory Peak', 'chart-4');

?>
	<div  id="chart-1"><!-- Fusion Charts will render here--></div>
	<div  id="chart-2"><!-- Fusion Charts will render here--></div>
	<div  id="chart-3"><!-- Fusion Charts will render here--></div>
	<div  id="chart-4"><!-- Fusion Charts will render here--></div>
   </body>
</html>


<?php

Class IWP_Debug_Chart{
	private $chart_meta;
	private $dataset;

	public function __construct($caption, $numbersuffix, $file, $graph_name, $chart_id){
		$this->init_chart_meta($caption, $numbersuffix);
		$this->read_logs($file);
		$this->plot_graph($graph_name, $chart_id);
	}

	private function plot_graph($graph_name, $chart_id){
		$encoded_data = $this->struct_data();
		$pieChart = new FusionCharts("line", $graph_name , "100%", 300, $chart_id, "json", $encoded_data);
		$pieChart->render();
	}

	private function struct_data(){
		$this->chart_meta['data'] = $this->dataset;
		return json_encode($this->chart_meta);
	}

	private function read_logs($file) {
		$file = fopen($file, "r");

		if(empty($file)){
			return false;
		}

		while(!feof($file)) {
			$data = explode('  ', fgets($file));

			if (empty($data[0]) || empty($data[1] ) ) {
				continue;
			}
			$this->dataset[] = array(
					"label" => $data[1],
					"value" => trim($data[2]),
					"color" => "008ee4",
					"stepSkipped" => false,
					"appliedSmartLabel" => true
				);
		}

		fclose($file);
	}

	private function init_chart_meta($caption, $numbersuffix){
		$this->chart_meta = array(
			"chart" => array(
				"caption" =>$caption,
				"numbersuffix" => $numbersuffix,
				"bgcolor" => "FFFFFF",
				"showalternatehgridcolor" => "0",
				"plotbordercolor" => "008ee4",
				"plotborderthickness" => "3",
				"showvalues" => "0",
				"divlinecolor" => "CCCCCC",
				"showcanvasborder" => "0",
				"tooltipbgcolor" => "00396d",
				"tooltipcolor" => "FFFFFF",
				"tooltipbordercolor" => "00396d",
				"numdivlines" => "20",
				"yaxisvaluespadding" => "20",
				"anchorbgcolor" => "008ee4",
				"anchorborderthickness" => "0",
				"showshadow" => "0",
				"showLabels" =>"0",
				"anchorradius" => "2",
				"chartrightmargin" => "25",
				"canvasborderalpha" => "0",
				"showborder" => "1",
			)
		);
	}
}