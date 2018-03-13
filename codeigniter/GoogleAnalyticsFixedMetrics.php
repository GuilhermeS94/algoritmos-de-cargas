<?php
defined('BASEPATH') OR exit('No direct script access allowed');
class BIAnalyticsApi extends CI_Controller{
    
    function __construct(){
        parent::__construct();
		$this->load->model(array('Analytics/M_ConfiguracoesAPI', 'Analytics/M_AnalyticsGeneric'));
		$this->load->helper('mydata');
		ini_set('max_execution_time', 0);//post por 30 segundos de solicitação, assim fica infinito 
    }

	public $contador = 0;
	public $funfou = Array();

	public function index()
	{
		require_once APPPATH.'/third_party/Google/vendor/autoload.php';	
		$configAPI = new M_ConfiguracoesAPI();
		$arrayConfig = $configAPI->ListAll(1);

		foreach ($arrayConfig as $report) {
			$report->Load($report->Id);

			//executar apenas do frango
			if($report->Id != 1) continue;

			$analytics = $this->initializeAnalyticsDinamic($report->GAMarcaInfo);			

			switch($report->TipoCargaId){

				case 1:
					$this->ReportDiario($analytics, $report);
				break;

				case 2:
					$this->ReportMenosXDias($analytics, $report);
				break;

				case 3:
					$this->ReportIntervaloDiaADia($analytics, $report);
				break;

				case 4:
					$this->ReportIntervaloTotal($analytics, $report);
				break;

				case 5:
					$this->ReportIniMesAteHoje($analytics, $report);
				break;

				default:
				continue;
			}
		}
		//$data['funfou'] = json_encode($this->funfou);
        //$this->load->view('admin/index', $data);
	}

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

	function GetResults(&$results) {
		$arrayResult = Array();
		if (count($results->getRows()) > 0) {

			$rows = $results->getRows();

			foreach($rows as $item){
				for($i=0,$j=0; $i < count($item); $i++,$j++){
					$arrayResult[] = $item[$i];					
				}
			}
			return $arrayResult;
		} else {
			//print "No results found.\n";
		}
	}

	/*
		Post que pega o report
	*/
	function getReportPerDay(&$analytics, $dia, $report){

		$this->Respiro($this->contador);
		$TABLE_ID = $report->GAMarcaInfo->TableId;//ga:ViewId -> TableId

		$retornoDay = $analytics->data_ga->get(
			$TABLE_ID,
			$dia,
			$dia,
			"ga:sessions,ga:pageviews,ga:pageviewsPerSession,ga:avgTimeOnPage,ga:bounces,ga:bounceRate,ga:users,ga:newUsers,ga:timeOnPage,ga:exits"
			);

		$this->contador++;

		return $retornoDay;
	}

	/*
		Post que pega o report num range de datas
	*/
	function getReportPerRange(&$analytics, $dia_ini, $dia_fim, $report){

		$this->Respiro($this->contador);
		$TABLE_ID = $report->GAMarcaInfo->TableId;//ga:ViewId -> TableId

		$retornoRange = $analytics->data_ga->get(
			$TABLE_ID,
			$dia_ini,
			$dia_fim,
			"ga:sessions,ga:pageviews,ga:pageviewsPerSession,ga:avgTimeOnPage,ga:bounces,ga:bounceRate,ga:users,ga:newUsers,ga:timeOnPage,ga:exits"
			);
		
		$this->contador++;
		return $retornoRange;
	}

	/*
		Gera Exception para mais de 100 requests por segundo
	*/
	function Respiro($inteiro){
		if($inteiro >= 90){
			sleep(2);
			$this->contador = 0;
		}
	}

	/*
		Reports
	*/
	function ReportDiario($analytics, $report){
		$dia = date('Y-m-d');
		$dadosBrutos = $this->getReportPerDay($analytics, $dia, $report);
				
		$dados = $this->GetResults($dadosBrutos, $report);
		$this->Carga($report, $dados, array("ini"=>$dia,"fim"=>$dia));			
	}

	function ReportMenosXDias($analytics, $report){
		$hoje = date('Y-m-d');

		for($i=0; $i <= $report->MenosNDias; $i++){
			//$GA = new M_AnalyticsGeneric();
			if($i > 0){
				$hoje = date('Y-m-d', strtotime($hoje." - 1 days"));
			}	

			$this->Respiro($this->contador);
			$dadosBrutos = $this->getReportPerDay($analytics, $hoje, $report);
				
			$dados = $this->GetResults($dadosBrutos, $report);
			$this->Carga($report, $dados, array("ini"=>$hoje,"fim"=>$hoje));
		}
	}

	function ReportIntervaloDiaADia($analytics, $report){

		$data_ini = convertStrDateToHTMLDate($report->StartDate);
		$data_fim = convertStrDateToHTMLDate($report->EndDate);

		while($data_ini <= $data_fim){
			$GA = new M_AnalyticsGeneric();

			$dadosBrutos = $this->getReportPerDay($analytics, $data_ini, $report);
				
			$dados = $this->GetResults($dadosBrutos, $report);
			$this->Carga($report, $dados, array("ini"=>$data_ini,"fim"=>$data_ini));
			
			$data_ini = Date('Y-m-d', strtotime("$data_ini + 1 days"));
		}
	}

	function ReportIntervaloTotal($analytics, $report){

		$data_ini = convertStrDateToHTMLDate($report->StartDate);
		$data_fim = convertStrDateToHTMLDate($report->EndDate);

		$dadosBrutos = $this->getReportPerRange($analytics, $data_ini, $data_fim, $report);
				
		$dados = $this->GetResults($dadosBrutos, $report);

		$this->Carga($report, $dados, array("ini"=>$data_ini,"fim"=>$data_fim));
	}

	function ReportIniMesAteHoje($analytics, $report){
		
		$hoje = date('Y-m-d');
		$data_ini = Date('Y-m-01', strtotime($hoje));

		while($data_ini <= $hoje){		
			
			$dadosBrutos = $this->getReportPerDay($analytics, $data_ini, $report);
				
			$dados = $this->GetResults($dadosBrutos, $report);
			$this->Carga($report, $dados, array("ini"=>$data_ini,"fim"=>$data_ini));
			
			$data_ini = Date('Y-m-d', strtotime("$data_ini + 1 days"));
		}
	}

	function Carga($report, $dados, $datas){

		$GA = new M_AnalyticsGeneric();
		$GA->StartDate = $datas["ini"];
		$GA->EndDate = $datas["fim"];
		
		$GA->ConfigId = $report->Id;
		$GA->WebPropertyId = $report->GAMarcaInfo->Property;
		$GA->ProfileName = $report->GAMarcaInfo->ViewNome;
		$GA->TableId = $report->GAMarcaInfo->TableId;
		$GA->Sessions = $dados[0];
		$GA->Pageviews = $dados[1];
		$GA->PageviewsPerSession = $dados[2];
		$GA->AvgTimeOnPage = $dados[3];
		$GA->Bounces = $dados[4];
		$GA->BouncesRate = $dados[5];
		$GA->Users = $dados[6];
		$GA->NewUsers = $dados[7];
		$GA->TimeOnPage = $dados[8];
		$GA->Exits = $dados[9];
		$GA->Actived = 1;

		$GA->Save();
		$funfou[] = $GA->Id;
	}
}
?>