#api imports
from apiclient.discovery import build
from oauth2client.client import flow_from_clientsecrets
from oauth2client.file import Storage
from oauth2client.tools import argparser, run_flow

#meus imports
from Modelos.YtMarcasCanais import YtMarcasCanais
from Modelos.YtConfiguracoes import YtConfiguracoes
from Modelos.YtDados import YtDados

#requisitos imports
import datetime
import time
import json
import httplib2
import os
import sys
import itertools

# --noauth_local_webserver
print("Begin process...\n{}".format(datetime.datetime.now().time()))

respiro = 0

listaConfig = YtConfiguracoes().ListarTodos(pActived=True)
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
        
def ReportDiario(confs):
    """
    Salva os dados do dia(dia atual)
    """
    global respiro
    dia = datetime.datetime.now().date() - datetime.timedelta(days=2)

    Respiro()
    resposta_canal = youtube_analytics.reports().query(
        ids="channel=={}".format(confs.YtMarcaCanal.YtCanalId),
        start_date=str(dia),
        end_date=str(dia),
        metrics="annotationClicks,cardClicks,cardTeaserClicks,estimatedMinutesWatched,views,averageViewDuration,averageViewPercentage,likes,dislikes,shares,comments,videosAddedToPlaylists,videosRemovedFromPlaylists,subscribersGained,subscribersLost"
        ).execute()  
    
    respiro = respiro + 1

    if not resposta_canal:
        return
    
    dias_reg = {"ini" : dia, "fim" : dia}
    Carga(confs, resposta_canal, dias_reg)
    

def ReportMenosXDias(confs):
    """
    Salva os Insights de hoje, e menos "X" dias
    atrás, sendo salvo registros dia a dia
    """
    global respiro
    hoje = datetime.datetime.now().date() - datetime.timedelta(days=2)

    for num in range(confs.MenosNDias):

        if num > 0:
            hoje = hoje - datetime.timedelta(days=1)
        
        Respiro()
        resposta_canal = youtube_analytics.reports().query(
            ids="channel=={}".format(confs.YtMarcaCanal.YtCanalId),
            start_date=hoje.strftime("%Y-%m-%d"),
            end_date=hoje.strftime("%Y-%m-%d"),
            metrics="annotationClicks,cardClicks,cardTeaserClicks,estimatedMinutesWatched,views,averageViewDuration,averageViewPercentage,likes,dislikes,shares,comments,videosAddedToPlaylists,videosRemovedFromPlaylists,subscribersGained,subscribersLost"
        ).execute() 

        respiro = respiro + 1
        if not resposta_canal:
            return

        dias_reg = {"ini" : hoje, "fim" : hoje}
        Carga(confs, resposta_canal, dias_reg)

def ReportIntervaloDiaADia(confs):
    """
    Salva os Insights em um intervalo de datas,
    um dia por vez
    """
    global respiro
    data_ini = confs.StartDate.date() 
    data_fim = confs.EndDate.date() 
    
    while data_ini <= data_fim:
        
        print(data_ini)

        Respiro()
        resposta_canal = youtube_analytics.reports().query(
            ids="channel=={}".format(confs.YtMarcaCanal.YtCanalId),
            start_date=data_ini.strftime("%Y-%m-%d"),
            end_date=data_ini.strftime("%Y-%m-%d"),
            metrics="annotationClicks,cardClicks,cardTeaserClicks,estimatedMinutesWatched,views,averageViewDuration,averageViewPercentage,likes,dislikes,shares,comments,videosAddedToPlaylists,videosRemovedFromPlaylists,subscribersGained,subscribersLost"
        ).execute()

        respiro = respiro + 1
        if not resposta_canal:
            data_ini = data_ini + datetime.timedelta(days=1)
            continue
        
        dias_reg = {"ini" : data_ini, "fim" : data_ini}
        Carga(confs, resposta_canal, dias_reg)
        data_ini = data_ini + datetime.timedelta(days=1)


def ReportIntervaloTotal(confs):
    """
    Salva os Insights em um intervalo de datas
    total
    """
    global respiro

    Respiro()
    resposta_canal = youtube_analytics.reports().query(
        ids="channel=={}".format(confs.YtMarcaCanal.YtCanalId),
        start_date=confs.StartDate.strftime("%Y-%m-%d"),
        end_date=confs.EndDate.strftime("%Y-%m-%d"),
        metrics="annotationClicks,cardClicks,cardTeaserClicks,estimatedMinutesWatched,views,averageViewDuration,averageViewPercentage,likes,dislikes,shares,comments,videosAddedToPlaylists,videosRemovedFromPlaylists,subscribersGained,subscribersLost"
    ).execute()  

    respiro = respiro + 1
    if not resposta_canal:
        return
    
    dias_reg = {"ini" : confs.StartDate, "fim" : confs.EndDate}
    Carga(confs, resposta_canal, dias_reg)




def Carga(confs, resposta_canal, dias_reg):
    """
    Função que parametriza os dados da API
    em objetos para salvar no banco
    """
    listaItens = list()
    for registro in resposta_canal.get("rows", []):
        item = YtDados()
        item.YtConfigId = confs.Id

        item.AnnotationClicks = registro[0]
        item.CardClicks = registro[1]
        item.CardTeaserClicks = registro[2]
        item.EstimatedMinutesWatched = registro[3]
        item.Views = registro[4]
        item.AverageViewDuration = registro[5]
        item.AverageViewPercentage = registro[6]
        item.Likes = registro[7]
        item.Dislikes = registro[8]
        item.Shares = registro[9]
        item.Comments = registro[10]
        item.VideosAddedToPlaylists = registro[11]
        item.VideosRemovedFromPlaylists = registro[12]
        item.SubscribersGained = registro[13]
        item.SubscribersLost = registro[14]

        item.StartDate = dias_reg["ini"]
        item.EndDate = dias_reg["fim"]
        item.Actived = True
        item.UserCreated = 1

        listaItens.append(item)

    YtDados().SalvarLista(listaItens)

for confs in listaConfig:
    
    #Bloco da API - Inicio-------------------------------------------------------------------------

    # The CLIENT_SECRETS_FILE variable specifies the name of a file that contains
    # the OAuth 2.0 information for this application, including its client_id and
    # client_secret. You can acquire an OAuth 2.0 client ID and client secret from
    # the {{ Google Cloud Console }} at
    # {{ https://cloud.google.com/console }}.
    # Please ensure that you have enabled the YouTube Data API for your project.
    # For more information about using OAuth2 to access the YouTube Data API, see:
    #   https://developers.google.com/youtube/v3/guides/authentication
    # For more information about the client_secrets.json file format, see:
    #   https://developers.google.com/api-client-library/python/guide/aaa_client_secrets
    CLIENT_SECRETS_FILE = "credenciais/{}.json".format(confs.YtMarcaCanal.YtCanalId)

    # This variable defines a message to display if the CLIENT_SECRETS_FILE is
    # missing.
    MISSING_CLIENT_SECRETS_MESSAGE = """
    WARNING: Please configure OAuth 2.0
    To make this sample run you will need to populate the client_secrets.json file
    found at:
    %s
    with information from the {{ Cloud Console }}
    {{ https://cloud.google.com/console }}
    For more information about the client_secrets.json file format, please visit:
    https://developers.google.com/api-client-library/python/guide/aaa_client_secrets
    """ % os.path.abspath(os.path.join(os.path.dirname(__file__),
                                    CLIENT_SECRETS_FILE))

    # This OAuth 2.0 access scope allows for read-only access to the authenticated
    # user's account, but not other types of account access.
    YOUTUBE_SCOPES = ["https://www.googleapis.com/auth/youtube.readonly",
    "https://www.googleapis.com/auth/yt-analytics-monetary.readonly",
    "https://www.googleapis.com/auth/yt-analytics.readonly"]
    #YOUTUBE_READONLY_SCOPE = "https://www.googleapis.com/auth/youtube.readonly"
    YOUTUBE_READONLY_SCOPE = " ".join(YOUTUBE_SCOPES)
    # YOUTUBE_API_SERVICE_NAME = "youtube"
    # YOUTUBE_API_VERSION = "v3"

    YOUTUBE_ANALYTICS_API_SERVICE_NAME = "youtubeAnalytics"
    YOUTUBE_ANALYTICS_API_VERSION = "v1"

    flow = flow_from_clientsecrets(CLIENT_SECRETS_FILE,
    message=MISSING_CLIENT_SECRETS_MESSAGE,
    scope=YOUTUBE_READONLY_SCOPE)

    #storage = Storage("%s-oauth2.json" % sys.argv[0])
    storage = Storage("%s-oauth2.json" % confs.YtMarcaCanal.YtCanalId)
    credentials = storage.get()

    if credentials is None or credentials.invalid:
        flags = argparser.parse_args()
        credentials = run_flow(flow, storage, flags)

    # youtube = build(YOUTUBE_API_SERVICE_NAME, YOUTUBE_API_VERSION,
    # http=credentials.authorize(httplib2.Http()))

    youtube_analytics = build(YOUTUBE_ANALYTICS_API_SERVICE_NAME,
    YOUTUBE_ANALYTICS_API_VERSION, http=credentials.authorize(httplib2.Http()))
    #Bloco da API - Fim----------------------------------------------------------------------------

    options = {
        1: ReportDiario,
        2: ReportMenosXDias,
        3: ReportIntervaloDiaADia,
        4: ReportIntervaloTotal
    }
    options[confs.TipoCargaId](confs)


print("End at: {}".format(datetime.datetime.now().time()))