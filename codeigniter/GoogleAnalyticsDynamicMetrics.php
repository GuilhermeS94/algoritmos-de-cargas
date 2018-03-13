<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class BIGAReports extends CI_Controller{

	public $contador = 0;
	public $funfou = Array();
    
    function __construct(){
        parent::__construct();
		$this->load->model(array('Analytics/M_GAReports', 'Analytics/M_Dimensions',
		 'Analytics/M_Metrics','Analytics/M_GAResultMetrics','Analytics/M_GAResultDimensions'));
		ini_set('max_execution_time', 0);//post por 30 segundos de solicitação, assim fica infinito 
		$this->load->helper('mydata');
		$this->load->helper('gaarrays');
    }

	public function index()
	{
		require_once APPPATH.'/third_party/Google/vendor/autoload.php';	
		$rep = new M_GAReports();
		$arrayConfig = $rep->ListAll(1);

		foreach ($arrayConfig as $report) {
			$report->Load($report->Id);
			$analytics = $this->initializeAnalyticsDinamic($report->GAMarcaInfo);			

			switch($report->TipoCargaId){

				case 1:
					$this->Respiro($this->contador);
					$this->ReportDiario($analytics, $report);
				break;

				case 2:
					$this->Respiro($this->contador);
					$this->ReportMenosXDias($analytics, $report);
				break;

				case 3:
					$this->Respiro($this->contador);
					$this->ReportIntervaloDiaADia($analytics, $report);
				break;

				case 4:
					$this->Respiro($this->contador);
					$this->ReportIntervaloTotal($analytics, $report);
				break;

				case 5:
					$this->Respiro($this->contador);
					$this->ReportMesAtualAteHoje($analytics, $report);
				break;

				default:
				continue;
			}
		}
		$data['funfou'] = json_encode($this->funfou);
        //$this->load->view('admin/index', $data);
	}

	/*
		inicialização da Conta
	*/
	public function initializeAnalyticsDinamic($configAPI)
	{
		$KEY_FILE_LOCATION = APPPATH."/credentials/".$configAPI->ArquivoCredentials;

		// Create and configure a new client object.
		$client = new Google_Client();
		$client->setApplicationName($configAPI->AccountNome);
		$client->setAuthConfig($KEY_FILE_LOCATION);
		$client->setScopes(['https://www.googleapis.com/auth/analytics.readonly']);
		$analytics = new Google_Service_Analytics($client);

		return $analytics;
	}

	/*
		Post que pega o report
	*/
	function getReportPerDay(&$analytics, $dia, $report, $filtrar){

		$this->Respiro($this->contador);
		$TABLE_ID = $report->GAMarcaInfo->TableId;//ga:ViewId -> TableId

		$optParams = array('dimensions' => convertArrayDimensionsToString($report->ArrayDimens));
		if($filtrar)
			$optParams['filters'] = "ga:channelGrouping==Paid Search;ga:keyword!~(not provided|not set|\+)";
		$retornoDay = $analytics->data_ga->get(
			$TABLE_ID,
			$dia,
			$dia,
			convertArrayMetricsToString($report->ArrayMetrics),
			$optParams);

		$this->contador++;

		return $retornoDay;
	}

	/*
		Post que pega o report num range de datas
	*/
	function getReportPerRange(&$analytics, $dia_ini, $dia_fim, $report, $filtrar){

		$this->Respiro($this->contador);
		$TABLE_ID = $report->GAMarcaInfo->TableId;//ga:ViewId -> TableId

		$optParams = array('dimensions' => convertArrayDimensionsToString($report->ArrayDimens));
		if($filtrar)
			$optParams['filters'] = "ga:channelGrouping==Paid Search;ga:keyword!~(not provided|not set|\+)";
		
		$retornoRange = $analytics->data_ga->get(
			$TABLE_ID,
			$dia_ini,
			$dia_fim,
			convertArrayMetricsToString($report->ArrayMetrics),
			$optParams);
		
		$this->contador++;
		return $retornoRange;
	}

	/*
		Gera Exception para mais de 100 requests por segundo
	*/
	function Respiro($inteiro){
		if($inteiro >= 80){
			sleep(2);
			$this->contador = 0;
		}
	}

	/*
		Formata o resultado do post em Arrays de Metrics e Dimensions
		para salvar no banco
	*/
	function GetResults(&$results, $report) {
		$arrayResult = Array();

		if (count($results->getRows()) > 0) {

			$rows = $results->getRows();

			foreach($rows as $item){
				$arrayResult[] = $item;
			}
			return $arrayResult;
		} else {
			//print "No results found.\n";
		}
	}

	/*
		Reports
	*/
	function ReportDiario($analytics, $report){
		$dia = date('Y-m-d');
		$dia = date('Y-m-d', strtotime($dia." - 1 days"));
		
		$dadosBrutos = $this->getReportPerDay($analytics, $dia, $report, Filtrar($report->ArrayDimens));
				
		$dados = $this->GetResults($dadosBrutos, $report);
		
		$this->Carga($dados, $report, array("ini"=>$dia,"fim"=>$dia));		
	}

	function ReportMenosXDias($analytics, $report){
		$hoje = date('Y-m-d');

		for($i=0; $i <= $report->MenosNDias; $i++){

			if($i > 0){
				$hoje = date('Y-m-d', strtotime($hoje." - 1 days"));
			}

			$this->Respiro($this->contador);
			$dadosBrutos = $this->getReportPerDay($analytics, $hoje, $report, Filtrar($report->ArrayDimens));
				
			$dados = $this->GetResults($dadosBrutos, $report);

			$this->Carga($dados, $report, array("ini"=>$hoje,"fim"=>$hoje));	
		}
	}

	function ReportIntervaloDiaADia($analytics, $report){

		$data_ini = convertStrDateToHTMLDate($report->StartDate);
		$data_fim = convertStrDateToHTMLDate($report->EndDate);

		while($data_ini <= $data_fim){

			$dadosBrutos = $this->getReportPerDay($analytics, $data_ini, $report, Filtrar($report->ArrayDimens));
				
			$dados = $this->GetResults($dadosBrutos, $report);
			
			$this->Carga($dados, $report, array("ini"=>$data_ini,"fim"=>$data_ini));	

			$data_ini = Date('Y-m-d', strtotime("$data_ini + 1 days"));
		}
	}

	function ReportIntervaloTotal($analytics, $report){

		$data_ini = convertStrDateToHTMLDate($report->StartDate);
		$data_fim = convertStrDateToHTMLDate($report->EndDate);

		$dadosBrutos = $this->getReportPerRange($analytics, $data_ini, $data_fim, $report, Filtrar($report->ArrayDimens));
				
		$dados = $this->GetResults($dadosBrutos, $report);

		$this->Carga($dados, $report, array("ini"=>$data_ini,"fim"=>$data_fim));
	}

	function ReportMesAtualAteHoje($analytics, $report){
		
		$hoje = date('Y-m-d');
		$data_ini = Date('Y-m-01', strtotime($hoje));

		while($data_ini <= $hoje){		
			
			$dadosBrutos = $this->getReportPerDay($analytics, $data_ini, $report, Filtrar($report->ArrayDimens));
				
			$dados = $this->GetResults($dadosBrutos, $report);

			$this->Carga($dados, $report, array("ini"=>$data_ini,"fim"=>$data_ini));

			$data_ini = Date('Y-m-d', strtotime("$data_ini + 1 days"));
		}
	}

	function Carga($dados, $report, $dias){

		$savedResultDimens = Array();
		for($h=0; $h < count($dados); $h++){
			$item = $dados[$h];
			$this->funfou[] = $dados[$h];
			$savedResultDimens = Array();

			for($i=0; $i < count($report->ArrayDimens); $i++){
				
				$GAResRep = new M_GAResultDimensions();
				$GAResRep->ReportId = $report->Id;
				$GAResRep->StartDate = $dias["ini"];
				$GAResRep->EndDate = $dias["fim"];
				$GAResRep->DimensionId = $report->ArrayDimens[$i]->Id;
				$GAResRep->DimensionValor = RemoverSpecialChars($item[$i]);
				$savedResultDimens[] = $GAResRep->Save();				
			}	

			$resultCount = 0;
			for($i=count($report->ArrayDimens); $i < count($item); $i++){
				
				$GAResRep = new M_GAResultMetrics();
				$GAResRep->ResultDimensionId = $savedResultDimens[$resultCount];
				$GAResRep->StartDate = $dias["ini"];
				$GAResRep->EndDate = $dias["fim"];
				$GAResRep->MetricId = $report->ArrayMetrics[$resultCount]->Id;
				$GAResRep->MetricValor = $item[$i];
				$GAResRep->Save();				
				$resultCount++;
			}			
		}

	}
}
?>