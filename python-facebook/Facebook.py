from Modelos.FBMarcasContas import FBMarcasContas
from Modelos.FbConfiguracoes import FbConfiguracoes
from Modelos.FBInsights import FBInsights
from Modelos.Credenciais import Credenciais

from facebookads import FacebookSession
from facebookads.api import FacebookAdsApi
from facebookads import objects
from facebookads.objects import (
    AdUser,
    Campaign,
    AdAccount,
    AdSet,
)

import datetime
import json
import time


print("Begin process...")
with open("Fim.txt", "a") as text_file:
    print("Inicio at: {}".format(datetime.datetime.now().time()), file=text_file)
respiro = 0
cred = Credenciais()
#inicializacao
### Setup session and api objects
session = FacebookSession(
    cred.MY_APP_ID,
    cred.MY_APP_SECRET,
    cred.MY_APP_ACCESS_TOKEN,
)
#session.timeout = 1000000
api = FacebookAdsApi(session)
FacebookAdsApi.set_default_api(api)

listaConfig = FbConfiguracoes().ListarTodos(pActived=True)
FBInsights().LimparPeriodo()

def ReportDiario(campaign, config):
    """
    Salva os Insights de um dia(dia atual)
    """
    global respiro
    
    ontem = datetime.datetime.now().date() - datetime.timedelta(days=1)
    menos3 = datetime.datetime.now().date() - datetime.timedelta(days=3)
    
    data_ini = menos3
    data_fim = ontem

    data_ini_camp = ontem
    data_fim_camp = ontem

    if "start_time" in campaign:
        data_ini_camp = datetime.datetime.strptime(campaign["start_time"][0:10], "%Y-%m-%d").date()    

    if "stop_time" in campaign:
        data_fim_camp = datetime.datetime.strptime(campaign["stop_time"][0:10], "%Y-%m-%d").date()

    while data_ini <= data_fim:
        try:
            print("Campanha: {}".format(campaign))        
            print("loop data ini: {}".format(data_ini))
            Respiro()

            cp_insights = campaign.get_insights(
                    fields = [objects.Insights.Field.clicks,
                                objects.Insights.Field.ctr,
                                objects.Insights.Field.impressions,
                                objects.Insights.Field.reach, 
                                objects.Insights.Field.frequency, 
                                objects.Insights.Field.spend,
                                objects.Insights.Field.actions,
                                objects.Insights.Field.date_stop,#nao necessario
                                objects.Insights.Field.date_start,#nao necessario
                                objects.Insights.Field.ad_name,#nao necessario
                                objects.Insights.Field.adset_name,#nao necessario
                                objects.Insights.Field.campaign_name,#nao necessario
                                objects.Insights.Field.campaign_id,#nao necessario
                                objects.Insights.Field.account_name,#nao necessario
                                objects.Insights.Field.account_id#nao necessario
                            ], 
                    params={
                            'time_range': {
                                'since': str(data_ini),
                                'until': str(data_ini)
                            }
                })

            respiro = respiro + 1
            if not cp_insights:
                print("Sem Insight dia: {}".format(data_ini))
                data_ini = data_ini + datetime.timedelta(days=1)
                continue
            camp_datas = {"ini" : data_ini_camp, "fim": data_fim_camp}
            Carga(config, cp_insights, camp_datas)
            data_ini = data_ini + datetime.timedelta(days=1)
        except Exception as ex:
            print(ex)
            continue

def ReportMenosXDias(campaign, config):
    """
    Salva os Insights de hoje, e menos "X" dias
    atrás, sendo salvo registros dia a dia
    """
    global respiro
    hoje = datetime.datetime.now().date()

    if datetime.datetime.strptime(campaign["start_time"][0:10], "%Y-%m-%d").date().year < 2000:
        print("Invalid Campaign")
        with open("Campanhas Invalidas.txt", "a") as text_file:
            print(campaign, file=text_file)
        return

    data_ini_camp = datetime.datetime.strptime(campaign["start_time"][0:10], "%Y-%m-%d").date()    
    data_fim_camp = datetime.datetime.now().date()
    if "stop_time" in campaign:
        data_fim_camp = datetime.datetime.strptime(campaign["stop_time"][0:10], "%Y-%m-%d").date()
        if data_fim_camp < config.StartDate.date():
            data_fim = data_fim_camp
    
    for num in range(config.MenosNDias):

        if num > 0:
            hoje = hoje - datetime.timedelta(days=1)
        
        Respiro()
        cp_insights = campaign.get_insights(
                fields = [objects.Insights.Field.clicks,
                            objects.Insights.Field.ctr,
                            objects.Insights.Field.impressions,
                            objects.Insights.Field.reach, 
                            objects.Insights.Field.frequency, 
                            objects.Insights.Field.spend,
                            objects.Insights.Field.actions,
                            objects.Insights.Field.date_stop,#nao necessario
                            objects.Insights.Field.date_start,#nao necessario
                            objects.Insights.Field.ad_name,#nao necessario
                            objects.Insights.Field.adset_name,#nao necessario
                            objects.Insights.Field.campaign_name,#nao necessario
                            objects.Insights.Field.campaign_id,#nao necessario
                            objects.Insights.Field.account_name,#nao necessario
                            objects.Insights.Field.account_id#nao necessario
                        ], 
                params={
                        'time_range': {
                            'since': str(hoje),
                            'until': str(hoje)
                        }
            })

        respiro = respiro + 1
        if not cp_insights:
            continue
        
        camp_datas = {"ini" : data_ini_camp, "fim": data_fim_camp}
        Carga(config,cp_insights, camp_datas)

def ReportIntervaloDiaADia(campaign, config):
    """
    Salva os Insights em um intervalo de datas,
    um dia por vez
    """
    global respiro
    
    data_ini = config.StartDate.date()        
    data_fim = config.EndDate.date() 
    
    data_ini_camp = datetime.datetime.strptime(campaign["start_time"][0:10], "%Y-%m-%d").date()
    data_fim_camp = datetime.datetime.now().date()

    if(data_ini_camp > data_ini):
        data_ini = data_ini_camp

    if "stop_time" in campaign:
        data_fim_camp = datetime.datetime.strptime(campaign["stop_time"][0:10], "%Y-%m-%d").date()

    if(data_fim_camp < data_fim):
        data_fim = data_fim_camp

    while data_ini <= data_fim:
        try:
            print("Campanha: {}".format(campaign))        
            print("loop data ini: {}".format(data_ini))
            Respiro()
            cp_insights = campaign.get_insights(
                fields = [objects.Insights.Field.clicks,
                            objects.Insights.Field.ctr,
                            objects.Insights.Field.impressions,
                            objects.Insights.Field.reach, 
                            objects.Insights.Field.frequency, 
                            objects.Insights.Field.spend,
                            objects.Insights.Field.actions,
                            objects.Insights.Field.date_stop,#nao necessario
                            objects.Insights.Field.date_start,#nao necessario
                            objects.Insights.Field.ad_name,#nao necessario
                            objects.Insights.Field.adset_name,#nao necessario
                            objects.Insights.Field.campaign_name,#nao necessario
                            objects.Insights.Field.campaign_id,#nao necessario
                            objects.Insights.Field.account_name,#nao necessario
                            objects.Insights.Field.account_id#nao necessario
                        ], 
                params={
                        'time_range': {
                            'since': str(data_ini),
                            'until': str(data_ini)
                        }
            })
            respiro = respiro + 1
            if not cp_insights:
                data_ini = data_ini + datetime.timedelta(days=1)
                continue
            
            camp_datas = {"ini" : data_ini_camp, "fim": data_fim_camp}
            Carga(config,cp_insights, camp_datas)
            print(cp_insights)
            print("------------------------------------")
            data_ini = data_ini + datetime.timedelta(days=1)
        except Exception as ex:
            print(ex)
            data_ini = data_ini + datetime.timedelta(days=1)
            continue

def ReportIntervaloTotal(campaign, config):
    """
    Salva os Insights em um intervalo de datas
    total
    """
    global respiro
    data_ini = config.StartDate.date()
    data_fim = config.EndDate.date()

    data_ini_camp = datetime.datetime.strptime(campaign["start_time"][0:10], "%Y-%m-%d").date()
    
    data_fim_camp = datetime.datetime.now().date()
    if "stop_time" in campaign:
        data_fim_camp = datetime.datetime.strptime(campaign["stop_time"][0:10], "%Y-%m-%d").date()
        if data_fim_camp < config.StartDate.date():
            data_fim = data_fim_camp
    try:
        Respiro()
        cp_insights = campaign.get_insights(
                fields = [objects.Insights.Field.clicks,
                            objects.Insights.Field.ctr,
                            objects.Insights.Field.impressions,
                            objects.Insights.Field.reach, 
                            objects.Insights.Field.frequency, 
                            objects.Insights.Field.spend,
                            objects.Insights.Field.actions,
                            objects.Insights.Field.date_stop,#nao necessario
                            objects.Insights.Field.date_start,#nao necessario
                            objects.Insights.Field.ad_name,#nao necessario
                            objects.Insights.Field.adset_name,#nao necessario
                            objects.Insights.Field.campaign_name,#nao necessario
                            objects.Insights.Field.campaign_id,#nao necessario
                            objects.Insights.Field.account_name,#nao necessario
                            objects.Insights.Field.account_id#nao necessario
                        ], 
                params={
                        'time_range': {
                            'since': str(data_ini),
                            'until': str(data_fim)
                        }
            })

        respiro = respiro + 1
        if not cp_insights:
            return
        
        camp_datas = {"ini" : data_ini_camp, "fim": data_fim_camp}
        Carga(config,cp_insights, camp_datas)
    except Exception as ex:
        print(ex)
        return

def ReportIntervaloMensal(campaign, config):
    """
    Salva os Insights do mês atual
    """
    global respiro
        
    hoje = datetime.datetime.now().date()

    data_ini_camp = datetime.datetime.strptime(campaign["start_time"][0:10], "%Y-%m-%d").date()
    
    data_fim_camp = datetime.datetime.now().date()
    if "stop_time" in campaign:
        data_fim_camp = datetime.datetime.strptime(campaign["stop_time"][0:10], "%Y-%m-%d").date()
    try:
        Respiro()
        cp_insights = campaign.get_insights(
                fields = [objects.Insights.Field.clicks,
                            objects.Insights.Field.ctr,
                            objects.Insights.Field.impressions,
                            objects.Insights.Field.reach, 
                            objects.Insights.Field.frequency, 
                            objects.Insights.Field.spend,
                            objects.Insights.Field.actions,
                            objects.Insights.Field.date_stop,#nao necessario
                            objects.Insights.Field.date_start,#nao necessario
                            objects.Insights.Field.ad_name,#nao necessario
                            objects.Insights.Field.adset_name,#nao necessario
                            objects.Insights.Field.campaign_name,#nao necessario
                            objects.Insights.Field.campaign_id,#nao necessario
                            objects.Insights.Field.account_name,#nao necessario
                            objects.Insights.Field.account_id#nao necessario
                        ], 
                params={
                        'date_preset':'this_month'
            })

        respiro = respiro + 1
        if not cp_insights:
            return
        
        camp_datas = {"ini" : data_ini_camp, "fim": data_fim_camp}
        Carga(config, cp_insights, camp_datas, True)
    except Exception as ex:
        print(ex)
        return


def Respiro():
    """
    Função que deixa um respiro de acordo com a 
    quantidade de requisições
    """
    global respiro
    if respiro > 50:
        print("aguarde respiro de 2s, qtd = {}".format(respiro))
        time.sleep(2)
        respiro = 0


def Carga(config, cp_insights, camp_datas, mes = False):
    """
    Função que efetivamente parametriza os insights
    em um objeto e salva no banco
    """
    listaIns = list()
    for cp_insight in cp_insights:
        item = FBInsights()
                
        item.FBConfigId = config.Id

        item.CampaignName = cp_insight["campaign_name"]
        item.CampaignId = cp_insight["campaign_id"]
        item.AccountName = cp_insight["account_name"]
        item.AccountId = cp_insight["account_id"]

        item.Clicks = cp_insight["clicks"]
        item.Ctr = cp_insight["ctr"]

        item.StartCampaign = camp_datas["ini"]
        item.StopCampaign = camp_datas["fim"]

        item.Frequency = cp_insight["frequency"]
        item.Impressions = cp_insight["impressions"]
        item.Reach = cp_insight["reach"]
        
        if "actions" in cp_insight:
            array_len = len(cp_insight["actions"])
            porta_vv = False
            porta_pe = False
            for acao in range(array_len):
                actItem = cp_insight["actions"][acao]

                if(actItem["action_type"] == "video_view") and porta_vv == False:
                    item.VideoViews = actItem["value"]
                    porta_vv = True

                if(actItem["action_type"] == "post_engagement") and porta_pe == False:
                    item.PostEngagement = actItem["value"]
                    porta_pe = True

                if porta_pe == True and porta_vv == True:
                    break

        item.Spend = cp_insight["spend"]
        item.StartDate = cp_insight["date_start"]
        item.EndDate = cp_insight["date_stop"]
        item.Actived = True
        item.UserCreated = 1
        listaIns.append(item)

    if mes:
        FBInsights().SalvarMensalLista(listaIns)    
    else:
        FBInsights().SalvarLista(listaIns)

for config in listaConfig:
    acc = AdAccount('act_{}'.format(config.MarcaConta.FBContaId))   

    options = {
        1: ReportDiario,
        2: ReportMenosXDias,
        3: ReportIntervaloDiaADia,
        4: ReportIntervaloTotal,
        5: ReportIntervaloMensal
    }
    
    data_ini_config = config.StartDate
    data_fim_config = config.EndDate
    hoje = datetime.datetime.now().date()

    for campaign in acc.get_campaigns(fields=[Campaign.Field.name,Campaign.Field.start_time, Campaign.Field.stop_time,Campaign.Field.status], params={'status' : [objects.Campaign.Status.paused, objects.Campaign.Status.active, objects.Campaign.Status.archived, objects.Campaign.Status.deleted]}):
        # campaign.remote_read(fields=[
        #     Campaign.Field.name,
        #     Campaign.Field.start_time,
        #     Campaign.Field.stop_time,
        #     Campaign.Field.statustruncaste
        # ])
        #print(campaign)
        config.StartDate = data_ini_config
        config.EndDate = hoje if (data_fim_config.date() > hoje) else data_fim_config
        
        if "start_time" in campaign:

            data_ini_camp = datetime.datetime.strptime(campaign["start_time"][0:10], "%Y-%m-%d")

            if data_ini_camp.year < 2000:
                print("Invalid Campaign")
                with open("Campanhas Invalidas.txt", "a") as text_file:
                    print(campaign, file=text_file)
                continue
                
        if "stop_time" in campaign:
            data_fim_camp = datetime.datetime.strptime(campaign["stop_time"][0:10], "%Y-%m-%d")
            
            if config.TipoCargaId == 3:   

                if data_fim_camp.date() < data_ini_config.date():
                    continue 

        print("------------------------------------")
        # Switch - Case
        options[config.TipoCargaId](campaign, config)
        
        
#FBInsights().IncluirDadosDw()
print("End at: {}".format(datetime.datetime.now().time()))
with open("Fim.txt", "a") as text_file:
    print("End at: {}".format(datetime.datetime.now().time()), file=text_file)