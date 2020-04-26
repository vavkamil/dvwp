<?php

	class FusionCharts {

		private $constructorOptions = array();

		private $constructorTemplate = '
		<script type="text/javascript">
			FusionCharts.ready(function () {
				new FusionCharts(__constructorOptions__);
			});
		</script>';

		private $renderTemplate = '
		<script type="text/javascript">
			FusionCharts.ready(function () {
				FusionCharts("__chartId__").render();
			});
		</script>
		';

		// constructor
		function __construct($type, $id, $width = 400, $height = 300, $renderAt, $dataFormat, $dataSource) {
			isset($type) ? $this->constructorOptions['type'] = $type : '';
			isset($id) ? $this->constructorOptions['id'] = $id : 'php-fc-'.time();
			isset($width) ? $this->constructorOptions['width'] = $width : '';
			isset($height) ? $this->constructorOptions['height'] = $height : '';
			isset($renderAt) ? $this->constructorOptions['renderAt'] = $renderAt : '';
			isset($dataFormat) ? $this->constructorOptions['dataFormat'] = $dataFormat : '';
			isset($dataSource) ? $this->constructorOptions['dataSource'] = $dataSource : '';

			$tempArray = array();
			foreach($this->constructorOptions as $key => $value) {
				if ($key === 'dataSource') {
					$tempArray['dataSource'] = '__dataSource__';
				} else {
					$tempArray[$key] = $value;
				}
			}

			$jsonEncodedOptions = json_encode($tempArray);

			if ($dataFormat === 'json') {
				$jsonEncodedOptions = preg_replace('/\"__dataSource__\"/', $this->constructorOptions['dataSource'], $jsonEncodedOptions);
			} elseif ($dataFormat === 'xml') {
				$jsonEncodedOptions = preg_replace('/\"__dataSource__\"/', '\'__dataSource__\'', $jsonEncodedOptions);
				$jsonEncodedOptions = preg_replace('/__dataSource__/', $this->constructorOptions['dataSource'], $jsonEncodedOptions);
			} elseif ($dataFormat === 'xmlurl') {
				$jsonEncodedOptions = preg_replace('/__dataSource__/', $this->constructorOptions['dataSource'], $jsonEncodedOptions);
			} elseif ($dataFormat === 'jsonurl') {
				$jsonEncodedOptions = preg_replace('/__dataSource__/', $this->constructorOptions['dataSource'], $jsonEncodedOptions);
			}
			$newChartHTML = preg_replace('/__constructorOptions__/', $jsonEncodedOptions, $this->constructorTemplate);

			echo $newChartHTML;
		}

		// render the chart created
		// It prints a script and calls the FusionCharts javascript render method of created chart
		function render() {
		   $renderHTML = preg_replace('/__chartId__/', $this->constructorOptions['id'], $this->renderTemplate);
		   echo $renderHTML;
		}

	}
?>
