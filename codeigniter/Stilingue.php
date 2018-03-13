<?php
defined('BASEPATH') OR exit('No direct script access allowed');
//http://localhost:8080/fabricaDeApi/index.php/admin/BIStilingue
class BIStilingue extends CI_Controller{
    
    function __construct(){
        parent::__construct();
		$this->load->helper('mydata');
		$this->load->helper('imagens');
        //ini_set('max_execution_time', 0);//post por 30 segundos de solicitação, assim fica infinito 
    }

    public $funfou = Array();

	public function index()
	{
		ini_set('max_execution_time', 0);//post por 30 segundos de solicitação, assim fica infinito 
		$this->load->model(array('Stilingue/StiFace/M_StiFbConfiguracoes', 'Stilingue/StiFace/M_StiFbImagensDash'));
		$configAPI = new M_StiFbConfiguracoes();
		$arrayConfig = $configAPI->ListAll(1);

		foreach ($arrayConfig as $item) {

            switch($item->TipoCargaId){

				case 1:
					$this->ReportDiario($item);
				break;

				case 3:
					$this->ReportIntervaloDiaADia($item);
				break;

				default:
				continue;
			}
		}
		
		//$data['funfou'] = json_encode($this->funfou);
        //$this->load->view('admin/stilingue/index', $data);
	}


	public function GetJsonFromURL($url){
		$dados = json_decode(file_get_contents($url),true);
		return $dados;
	}
    /*
		Distribuição de dados
	*/
	function ReportDiario($configObj){
		$dia_fim = date('Y-m-d 23:59:00');
		$dia_fim = date('Y-m-d 23:59:00', strtotime($dia_fim." - 1 days"));
        $dia_ini = date('Y-m-d 00:00:00');
		$dia_ini = date('Y-m-d 00:00:00', strtotime($dia_ini." - 1 days"));
		$configObj->StartDate = $dia_ini;
		$configObj->EndDate = $dia_fim;
        switch($configObj->Report->Url){

				case 'fanbase':
					$url = 'https://stilingueapi.appspot.com/performance/facebook/'.$configObj->Report->Url.'/'.$configObj->MarcaToken->Token.'?start_date='.convertSqlDateToStiDate($dia_ini).'&end_date='.convertSqlDateToStiDate($dia_fim);
					$dados = $this->GetJsonFromURL($url);
					$this->EvolucaoBaseFas($dados, $configObj->MarcaTokenId);
					$this->funfou[] = $url;
				break;

				case 'pagelikes':
					$url = 'https://stilingueapi.appspot.com/performance/facebook/'.$configObj->Report->Url.'/'.$configObj->MarcaToken->Token.'?start_date='.convertSqlDateToStiDate($dia_ini).'&end_date='.convertSqlDateToStiDate($dia_fim);
					$dados = $this->GetJsonFromURL($url);
					$this->CampanhaPageLikes($dados, $configObj->MarcaTokenId);
					$this->funfou[] = $url;
				break;

				case 'likesorigin':
					$url = 'https://stilingueapi.appspot.com/performance/facebook/'.$cofigObj->Report->Url.'/'.$configObj->MarcaToken->Token.'?start_date='.convertSqlDateToStiDate($dia_ini).'&end_date='.convertSqlDateToStiDate($dia_fim);
					$dados = $this->GetJsonFromURL($url);
					$this->OrigemLikes($dados, $configObj->MarcaTokenId);
					$this->funfou[] = $url;
				break;

				case 'demographics':
					$url = 'https://stilingueapi.appspot.com/performance/facebook/'.$configObj->Report->Url.'/'.$configObj->MarcaToken->Token.'?start_date='.convertSqlDateToStiDate($dia_ini).'&end_date='.convertSqlDateToStiDate($dia_fim);
					$dados = $this->GetJsonFromURL($url);
					$this->DadosDemograficos($dados, $configObj->MarcaTokenId);
					$this->funfou[] = $url;
				break;

				case 'viewclickctr':
					$url = 'https://stilingueapi.appspot.com/performance/facebook/'.$configObj->Report->Url.'/'.$configObj->MarcaToken->Token.'?start_date='.convertSqlDateToStiDate($dia_ini).'&end_date='.convertSqlDateToStiDate($dia_fim);
					$dados = $this->GetJsonFromURL($url);
					$this->ComparativosResultados($dados, $configObj->MarcaTokenId);
					$this->funfou[] = $url;
				break;

				case 'postsxinteractions':
					$url = 'https://stilingueapi.appspot.com/performance/facebook/'.$configObj->Report->Url.'/'.$configObj->MarcaToken->Token.'?start_date='.convertSqlDateToStiDate($dia_ini).'&end_date='.convertSqlDateToStiDate($dia_fim);
					$dados = $this->GetJsonFromURL($url);
					$this->PublicacoesVSInteracoes($dados, $configObj->MarcaTokenId);
					$this->funfou[] = $url;
				break;

				case 'commentssentiment':
					$url = 'https://stilingueapi.appspot.com/performance/facebook/'.$configObj->Report->Url.'/'.$configObj->MarcaToken->Token.'?start_date='.convertSqlDateToStiDate($dia_ini).'&end_date='.convertSqlDateToStiDate($dia_fim);
					$dados = $this->GetJsonFromURL($url);
					$this->SentimentoComentarios($dados, $configObj);
					$this->funfou[] = $url;
				break;

				case 'reach':
					$url = 'https://stilingueapi.appspot.com/performance/facebook/'.$configObj->Report->Url.'/'.$configObj->MarcaToken->Token.'?start_date='.convertSqlDateToStiDate($dia_ini).'&end_date='.convertSqlDateToStiDate($dia_fim);
					$dados = $this->GetJsonFromURL($url);
					$this->AlcanceVSEngajamento($dados, $configObj->MarcaTokenId);
					$this->funfou[] = $url;
				break;

				case 'wordcloud':
					$url = 'https://stilingueapi.appspot.com/performance/facebook/'.$configObj->Report->Url.'/'.$configObj->MarcaToken->Token.'?start_date='.convertSqlDateToStiDate($dia_ini).'&end_date='.convertSqlDateToStiDate($dia_fim);
					$dados = $this->GetJsonFromURL($url);
					$configObj->StartDate = $dia_ini;
					$configObj->EndDate = $dia_fim;
					$this->WordCloudComentarios($dados, $configObj);
					$this->funfou[] = $url;
				break;

				case 'posts':
					$url = 'https://stilingueapi.appspot.com/performance/facebook/'.$configObj->Report->Url.'/'.$configObj->MarcaToken->Token.'?start_date='.convertSqlDateToStiDate($dia_ini).'&end_date='.convertSqlDateToStiDate($dia_fim);
					$url .= "&offset=0&limit=20";//offset 0 primeira pagina de resultados, 20 quantidade maxima de resultados por pagina
					$dados = $this->GetJsonFromURL($url);
					$this->Publicacoes($dados, $configObj, array("ini" => $dia_ini, "fim" => $dia_fim));
					$this->funfou[] = $url;
				break;

				default:
				break;
			}
	}

    function ReportIntervaloDiaADia($configObj){

		$dia_fim = date('Y-m-d 23:59:00', strtotime($configObj->EndDate));
		$dia_ini = Date('Y-m-d 00:00:00', strtotime($configObj->StartDate));

		//while($dia_ini <= $dia_fim){
			//$dia_ini_pro = date('Y-m-d 00:00:00', strtotime($dia_ini));
			//$dia_fim_pro = date('Y-m-d 23:59:00', strtotime($dia_ini));
			// print_r($dia_ini_pro);
			// print_r($dia_fim_pro);

			switch($configObj->Report->Url){

				case 'fanbase':
					$url = 'https://stilingueapi.appspot.com/performance/facebook/'.$configObj->Report->Url.'/'.$configObj->MarcaToken->Token;
					$url .= '?start_date='.convertSqlDateToStiDate($dia_ini).'&end_date='.convertSqlDateToStiDate($dia_fim);		
					$dados = $this->GetJsonFromURL($url);
					$this->EvolucaoBaseFas($dados, $configObj->MarcaTokenId);
					$this->funfou[] = $url;
				break;

				case 'pagelikes':
					$url = 'https://stilingueapi.appspot.com/performance/facebook/'.$configObj->Report->Url.'/'.$configObj->MarcaToken->Token;
					$url .= '?start_date='.convertSqlDateToStiDate($dia_ini).'&end_date='.convertSqlDateToStiDate($dia_fim);		
					$dados = $this->GetJsonFromURL($url);
					$this->CampanhaPageLikes($dados, $configObj->MarcaTokenId);
					$this->funfou[] = $url;
				break;

				case 'likesorigin':
					$url = 'https://stilingueapi.appspot.com/performance/facebook/'.$configObj->Report->Url.'/'.$configObj->MarcaToken->Token;
					$url .= '?start_date='.convertSqlDateToStiDate($dia_ini).'&end_date='.convertSqlDateToStiDate($dia_fim);		
					$dados = $this->GetJsonFromURL($url);
					$this->OrigemLikes($dados, $configObj->MarcaTokenId);
					$this->funfou[] = $url;
				break;

				case 'demographics':
					$url = 'https://stilingueapi.appspot.com/performance/facebook/'.$configObj->Report->Url.'/'.$configObj->MarcaToken->Token;
					$url .= '?start_date='.convertSqlDateToStiDate($dia_ini).'&end_date='.convertSqlDateToStiDate($dia_fim);		
					$dados = $this->GetJsonFromURL($url);
					$this->DadosDemograficos($dados, $configObj->MarcaTokenId);
					$this->funfou[] = $url;
				break;

				case 'viewclickctr':
					$url = 'https://stilingueapi.appspot.com/performance/facebook/'.$configObj->Report->Url.'/'.$configObj->MarcaToken->Token;
					$url .= '?start_date='.convertSqlDateToStiDate($dia_ini).'&end_date='.convertSqlDateToStiDate($dia_fim);		
					$dados = $this->GetJsonFromURL($url);
					$this->ComparativosResultados($dados, $configObj->MarcaTokenId);
					$this->funfou[] = $url;
				break;

				case 'postsxinteractions':
					$url = 'https://stilingueapi.appspot.com/performance/facebook/'.$configObj->Report->Url.'/'.$configObj->MarcaToken->Token;
					$url .= '?start_date='.convertSqlDateToStiDate($dia_ini).'&end_date='.convertSqlDateToStiDate($dia_fim);		
					$dados = $this->GetJsonFromURL($url);
					$this->PublicacoesVSInteracoes($dados, $configObj->MarcaTokenId);
					$this->funfou[] = $url;
				break;

				case 'commentssentiment':
					$url = 'https://stilingueapi.appspot.com/performance/facebook/'.$configObj->Report->Url.'/'.$configObj->MarcaToken->Token;
					$url .= '?start_date='.convertSqlDateToStiDate($dia_ini).'&end_date='.convertSqlDateToStiDate($dia_fim);		
					$dados = $this->GetJsonFromURL($url);
					$this->SentimentoComentarios($dados, $configObj);
					$this->funfou[] = $url;
				break;

				case 'reach':
					$url = 'https://stilingueapi.appspot.com/performance/facebook/'.$configObj->Report->Url.'/'.$configObj->MarcaToken->Token;
					$url .= '?start_date='.convertSqlDateToStiDate($dia_ini).'&end_date='.convertSqlDateToStiDate($dia_fim);		
					$dados = $this->GetJsonFromURL($url);
					$this->AlcanceVSEngajamento($dados, $configObj->MarcaTokenId);
					$this->funfou[] = $url;
				break;

				case 'wordcloud':

					$sti_dia_ini = date('Y-m-d 00:00:00', strtotime($configObj->StartDate));
					$sti_dia_fim = date('Y-m-d 23:59:00', strtotime($configObj->StartDate));

					$data_ini = date('Y-m-d', strtotime($configObj->StartDate));
					$data_fim = date('Y-m-d', strtotime($configObj->EndDate));

					while($data_ini <= $data_fim){

						$url = 'https://stilingueapi.appspot.com/performance/facebook/'.$configObj->Report->Url.'/'.$configObj->MarcaToken->Token;
						$url .= '?start_date='.convertSqlDateToStiDate($sti_dia_ini).'&end_date='.convertSqlDateToStiDate($sti_dia_fim);
						
						$dados = $this->GetJsonFromURL($url);

						$configObj->StartDate = $sti_dia_ini;
						$configObj->EndDate = $sti_dia_fim;

						$this->WordCloudComentarios($dados, $configObj);
						$this->funfou[] = $url;

						$data_ini = date('Y-m-d', strtotime("$data_ini + 1 days"));
						$sti_dia_ini = date('Y-m-d 00:00:00', strtotime($data_ini));
						$sti_dia_fim = date('Y-m-d 23:59:00', strtotime($data_ini));
					}


				break;

				case 'posts':
					$url = 'https://stilingueapi.appspot.com/performance/facebook/'.$configObj->Report->Url.'/'.$configObj->MarcaToken->Token;
					$url .= '?start_date='.convertSqlDateToStiDate($dia_ini).'&end_date='.convertSqlDateToStiDate($dia_fim);		
					$url .= "&offset=0&limit=20";//offset 0 primeira pagina de resultados, 20 quantidade maxima de resultados por pagina

					$dados = $this->GetJsonFromURL($url);
					$this->Publicacoes($dados, $configObj, array("ini" => $dia_ini, "fim" => $dia_fim));
					$this->funfou[] = $url;
				break;

				default:
				break;
			}
			//$dia_ini = Date('Y-m-d', strtotime("$dia_ini + 1 days"));
		//}
	}

    /*
        Reports
    */

	function AlcanceVSEngajamento($json, $MarcaTokenId){//OK
		$this->load->model('Stilingue/StiFace/M_StiFbAlcanceVSEngajamento');
		$this->load->helper('mydata');
		
		$dadosBrutos = $json["results"];
        $arrayList = Array();

        for($i = 0; $i < count($dadosBrutos[0]["data"]); $i++){   
            $obj = new M_StiFbAlcanceVSEngajamento();
            $item = $dadosBrutos;

            $obj->MarcaTokenId = $MarcaTokenId;
            $obj->UserCreated = 1;

            $obj->Audiencia = $item[0]["data"][$i]["value"];
            $obj->IndiceEngajamento = $item[1]["data"][$i]["value"];
                        
            $obj->StartDate = convertStiDateToSqlDate($item[0]["data"][$i]["date"]);
            $obj->EndDate = convertStiDateToSqlDate($item[0]["data"][$i]["date"]);

            $obj->Save();                      
        }
	}

	function CampanhaPageLikes($json, $MarcaTokenId){//OK
		$this->load->model('Stilingue/StiFace/M_StiFbCampanhaPageLikes');
		$this->load->helper('mydata');
		$dadosBrutos = $json["results"];
		
		$arrayList = Array();

        for($i = 0; $i < count($dadosBrutos[0]["data"]); $i++){   
            $obj = new M_StiFbCampanhaPageLikes();

            $itemDataNF = $dadosBrutos[0]["data"][$i];
            $itemDataMNF = $dadosBrutos[1]["data"]["value"];
            $itemDataNFP = $dadosBrutos[2]["data"][$i];
            $itemDataMNFP = $dadosBrutos[3]["data"]["value"];

            $obj->NovosFas = $itemDataNF["value"];
            $obj->MediaNovosFas = $itemDataMNF;
            $obj->NovosFasPagos = $itemDataNFP["value"];
            $obj->MediaNovosFasPagos = $itemDataMNFP;

            $obj->MarcaTokenId = $MarcaTokenId;
            $obj->UserCreated = 1;

            $obj->StartDate = convertStiDateToSqlDate($itemDataNF["date"]);
            $obj->EndDate = convertStiDateToSqlDate($itemDataNF["date"]);

            $obj->Save();                        
        }
		
	}

	function ComparativosResultados($json, $MarcaTokenId){//OK
		$this->load->model('Stilingue/StiFace/M_StiFbComparativosResultados');
		$this->load->helper('mydata');
		$dadosBrutos = $json["results"];
        $arrayList = Array();

        for($i = 0; $i < count($dadosBrutos[0]["data"]); $i++){   
            $obj = new M_StiFbComparativosResultados();
            $item = $dadosBrutos;

            $obj->MarcaTokenId = $MarcaTokenId;
            $obj->UserCreated = 1;

            $obj->Alcance = $item[0]["data"][$i]["value"];
            $obj->Cliques = $item[1]["data"][$i]["value"];
            $obj->CTR = $item[2]["data"][$i]["value"];
                        
            $obj->StartDate = convertStiDateToSqlDate($item[0]["data"][$i]["date"]);
            $obj->EndDate = convertStiDateToSqlDate($item[0]["data"][$i]["date"]);

            $obj->Save();                     
        }		
	}

	function DadosDemograficos($json, $MarcaTokenId){//OK
		$this->load->model('Stilingue/StiFace/M_StiFbDadosDemograficos');
		$arrayList = Array();

        for($i = 0; $i < count($json[0]["data"]); $i++){            
            $item = $json[0]["data"][$i];

            for($j=0; $j < count($item["data"]); $j++){
                
                $obj = new M_StiFbDadosDemograficos();
                $obj->UserCreated = 1;  
                $obj->MarcaTokenId = $MarcaTokenId;

                switch($item["name"]){
                    case 'Fãs Homens':

                        $obj->Publico = $item["data"][$j]["label"];
                        $obj->FasHomens = $item["data"][$j]["value"];

                    break;

                    case 'Homens Alcançados':

                        $obj->Publico = $item["data"][$j]["label"];
                        $obj->HomensAlcancados = $item["data"][$j]["value"];

                    break;

                    case 'Fãs Mulheres':

                        $obj->Publico = $item["data"][$j]["label"];
                        $obj->FasMulheres = $item["data"][$j]["value"];

                    break;

                    case 'Mulheres Alcançados':

                        $obj->Publico = $item["data"][$j]["label"];
                        $obj->FasMulheresAlcancadas = $item["data"][$j]["value"];

                    break;
                }

                $obj->StartDate = date("Y-m-d");
                $obj->EndDate = date("Y-m-d");
                      
				$arrayList[] = $obj;  
            }            
        }

        for($i = 0; $i < 7; $i++){

            $arrayList[$i]->HomensAlcancados = $arrayList[$i + 7]->HomensAlcancados;
            $arrayList[$i]->FasMulheres = $arrayList[$i + 14]->FasMulheres;
            $arrayList[$i]->FasMulheresAlcancadas = $arrayList[$i + 21]->FasMulheresAlcancadas;

            $arrayList[$i]->Save();
        }		
	}

	function EvolucaoBaseFas($json, $MarcaTokenId){//OK
		$this->load->model('Stilingue/StiFace/M_StiFbEvolucaoBaseFas');
		$this->load->helper('mydata');
		
		//$dadosUrl = json_decode(file_get_contents('https://stilingueapi.appspot.com/performance/facebook/fanbase/5672704850001920?start_date=201608010000&end_date=201608221200'),true);
        $dadosBrutos = $json["results"];
        $arrayList = Array();

        for($i = 0; $i < count($dadosBrutos[0]["data"]); $i++){
            $obj = new M_StiFbEvolucaoBaseFas();
            $obj->UserCreated = 1;//Guilherme Ferreira
            $obj->MarcaTokenId = $MarcaTokenId;
            $obj->DiasComAtivacao = $dadosBrutos[0]["data"][$i]["value"];
            $obj->NovosFas = $dadosBrutos[1]["data"][$i]["value"];
            $obj->Cancelados = $dadosBrutos[2]["data"][$i]["value"];
            $obj->FanBase = $dadosBrutos[3]["data"][$i]["value"];

            $obj->StartDate = convertStiDateToSqlDate($dadosBrutos[0]["data"][$i]["date"]);
            $obj->EndDate = convertStiDateToSqlDate($dadosBrutos[0]["data"][$i]["date"]);

            $obj->Save();
		}
	}

	function OrigemLikes($json, $MarcaTokenId){//Corrigir Array Json, colchetes ao invés de chaves
		$this->load->model('Stilingue/StiFace/M_StiFbOrigemLikes');
		$this->load->helper('mydata');
		$obj = new M_StiFbOrigemLikes();

		foreach($json as $item){

			switch($item['name']){
				case 'Origem de Likes':
					$obj->TimeLine = $item['data']['value'];
				break;

				case 'Interações Timeline':
					$obj->InteracoesTimeLine = $json['Interações Timeline']['data']['value'];
				break;

				case 'Dark Posts':
					$obj->DarkPosts = $json['Dark Posts']['data']['value'];
				break;

				case 'Interações DarkPosts':
					$obj->InteracoesDarkPosts = $json['Interações DarkPosts']['data']['value'];
				break;

				default:
				break;
			}
		}
		
		$obj->StartDate = convertStrDateToSqlDate($json['TimeLine']['data']['date']);
		$obj->EndDate = convertStrDateToSqlDate($json['TimeLine']['data']['date']);
		
	}

	function PublicacoesVSInteracoes($json, $MarcaTokenId){//OK
		$this->load->model('Stilingue/StiFace/M_StiFbPublicacoesVSInteracoes');
        $this->load->helper('mydata');
        $dadosBrutos = $json["results"];
        $arrayList = Array();

        for($i = 0; $i < count($dadosBrutos[0]["data"]); $i++){
            $obj = new M_StiFbPublicacoesVSInteracoes();
            $obj->UserCreated = 1;  
            $obj->MarcaTokenId = $MarcaTokenId;
            $obj->TimeLine = $dadosBrutos[0]["data"][$i]["value"];
            $obj->InteracoesTimeLine = $dadosBrutos[1]["data"][$i]["value"];
            $obj->DarkPosts = $dadosBrutos[2]["data"][$i]["value"];
            $obj->InteracoesDarkPosts = $dadosBrutos[3]["data"][$i]["value"];

            $obj->StartDate = convertStiDateToSqlDate($dadosBrutos[0]["data"][$i]["date"]);
            $obj->EndDate = convertStiDateToSqlDate($dadosBrutos[0]["data"][$i]["date"]);

            $obj->Save();
        }
	}

	function SentimentoComentarios($json, $configObj){//OK
		$this->load->model('Stilingue/StiFace/M_StiFbSentimentoComentarios');
		$this->load->helper('mydata');
		$dadosBrutos = $json["results"];        
		
		$arrayList = Array();

        for($i = 0; $i < count($dadosBrutos[0]["data"]); $i++){   
            $obj = new M_StiFbSentimentoComentarios();
            $item = $dadosBrutos[0]["data"][$i];//data[{...}] e name = Date

            $obj->StiConfiguracaoId = $configObj->Id;
            $obj->UserCreated = 1;

            $obj->Positivo = $item["data"][0]["value"];
            $obj->Negativo = $item["data"][1]["value"];
            $obj->Neutro = $item["data"][2]["value"];
            $obj->TotalComentarios = $item["data"][3]["value"];
                        
            $obj->StartDate = convertStiDateToSqlDate($item["name"]);
            $obj->EndDate = convertStiDateToSqlDate($item["name"]);

            $obj->Save();
        }		
	}

	function WordCloudComentarios($json, $objWcc){//OK
		$this->load->model('Stilingue/StiFace/M_StiFbWordCloudComentarios');
		$dadosBrutos = $json["results"];
		
		$arrayList = Array();

        for($i = 0; $i < count($dadosBrutos); $i++){   
            $obj = new M_StiFbWordCloudComentarios();
            $item = $dadosBrutos[$i];

            $obj->MarcaTokenId = $objWcc->MarcaTokenId;
            $obj->UserCreated = 1;

            $obj->Palavra = $item["name"];
            $obj->Frequencia = $item["value"];
                        
            $obj->StartDate = $objWcc->StartDate;
            $obj->EndDate = $objWcc->EndDate;

            $obj->Save();                        
        }		
	}

	function Publicacoes($json, $objPub, $datas){
		$this->load->model('Stilingue/StiFace/M_StiFbPosts');

		$dadosBrutos = $json["posts"];		
		$paginacao = $json["next_offset"];
		$total = $json["total_posts"];
		
		for($i=0; $i < count($dadosBrutos); $i++){   

			$item = $dadosBrutos[$i];
			$obj = new M_StiFbPosts();

			$obj->StiConfiguracaoId = $objPub->Id;
			$obj->MarcaTokenId = $objPub->MarcaTokenId;
			$obj->UserCreated = 1;
                                        
			$obj->VideoPlays = $item["videoplays"];
            $obj->UserName = $item["username"];
			if(isset($item["fb_views"]))
				$obj->FbViews = $item["fb_views"];
            $obj->PageId = $item["uid"];
            $obj->Descricao = $item["text"];
            $obj->Positivo = $item["comments_polarities"]["positive"];
            $obj->Negativo = $item["comments_polarities"]["negative"];
            $obj->Neutro = $item["comments_polarities"]["neutral"];
            $obj->PostId = $item["pid"];
			if(isset($item["fb_reach_organic"]))
				$obj->FbReachOrganic = $item["fb_reach_organic"];
			if(isset($item["fb_impressions_organic"]))
				$obj->FbImpressionsOrganic = $item["fb_impressions_organic"];
            $obj->Likes = $item["likes"];
            $obj->Replied = ($item["replied"] == true)? 1 : 0;//bit
            if(isset($item["from_integration"]))
				$obj->FromIntegration = ($item["from_integration"] == true)? 1 : 0;//bit			
			if(isset($item["fb_reach_paid"]))
				$obj->FbReachPaid = $item["fb_reach_paid"];
            if(isset($item["update_time_ago"]))
				$obj->UpdateTimeAgo = $item["update_time_ago"];			
			if(isset($item["fb_engaged_users"]))
				$obj->FbEngagedUsers = $item["fb_engaged_users"];
            $obj->IsHidden = ($item["is_hidden"] == true)? 1 : 0;//bit
			if(isset($item["fb_impressions_viral"]))
				$obj->FbImpressionsViral = $item["fb_impressions_viral"];
            $obj->Comments = $item["comments"];
            $obj->LongPostedAt = $item["long_posted_at"];
            $obj->Verified = ($item["verified"] == true)? 1 : 0;//bit
            $obj->Sentiment = $item["sentiment"];
            $obj->Title = $item["title"];
            $obj->PostadoEm = convertBrDateToSqlDate($item["posted_at"]);
            $obj->UserUrl = $item["user_url"];
            if(isset($item["fb_engaged_fan"]))
				$obj->FbEngagedFan = $item["fb_engaged_fan"];
            $obj->Shares = $item["shares"];
            $obj->PostUrl = $item["post_url"];
            $obj->Hot = $item["hot"];
            $obj->Followers = $item["followers"];
            $obj->MetricsUpdatedAt = convertBrDateToSqlDate($item["metrics_updated_at"]);
            $obj->Type = $item["type"];
            $obj->Channel = $item["channel"];
			$obj->UserImageUrl = $item["user_image_url"];
            $obj->PostUserImageUrl = $item["post_user_image_url"];
            $obj->Interactions = $item["interactions"];
			if(isset($item["fb_impressions"]))
				$obj->FbImpressions = $item["fb_impressions"];
			if(isset($item["fb_clicks"]))
				$obj->FbClicks = $item["fb_clicks"];
			if(isset($item["fb_engagement_rate"]))
				$obj->FbEngagementRate = $item["fb_engagement_rate"];
			if(isset($item["fb_impressions_paid"]))
				$obj->FbImpressionsPaid = $item["fb_impressions_paid"];
            $obj->AAAScore = $item["AAA_score"];
            $obj->Emotion = $item["emotion"];
            if(isset($item["status"]))
				$obj->Status = $item["status"];			
            $obj->HotPost = ($item["hot_post"] == true)? 1 : 0;//bit
            $obj->Name = $item["name"];
			if(isset($item["fb_reach"]))
				$obj->FbReach = $item["fb_reach"];
            $obj->Gender = $item["gender"];
            $obj->Spam = ($item["spam"] == true)? 1 : 0;//bit
            $obj->Favorite = $item["favorite"];
			if(isset($item["image_url"])){
				$obj->Imagem = $item["image_url"];
				$obj->ImagemSti = $item["image_url"];
			}
			$obj->CommentsAnswers = $item["comments_answers"]["total"];
			$obj->CommentsAnswered = $item["comments_answers"]["answered"];
			$obj->StartDate = $datas["ini"];
			$obj->EndDate = $datas["fim"];

			$obj->Save();   
			//$obj->Imagem = BaixarImagemPost($obj->Imagem, $item->Id);
			$obj->Imagem = BaixarImagemPost($obj);
			$obj->AtualizaImagem();
		}

		if($paginacao == $total) return;

		$url = 'https://stilingueapi.appspot.com/performance/facebook/'.$objPub->Report->Url.'/'.$objPub->MarcaToken->Token;
		$url .= '?start_date='.convertSqlDateToStiDate($objPub->StartDate).'&end_date='.convertSqlDateToStiDate($objPub->EndDate)."&offset=".$paginacao."&limit=20";			
		$dados = json_decode(file_get_contents($url),true);			
		$this->Publicacoes($dados, $objPub, $datas);		
	}
}
?>