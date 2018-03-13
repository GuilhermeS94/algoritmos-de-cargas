from datetime import datetime, timedelta
import time
import httplib2
import os
import sys

from apiclient.discovery import build
from apiclient.errors import HttpError
from oauth2client.client import flow_from_clientsecrets
from oauth2client.file import Storage
from oauth2client.tools import argparser, run_flow


#meus imports
from Modelos.YtMarcasCanais import YtMarcasCanais
from Modelos.YtConfiguracoes import YtConfiguracoes
from Modelos.YtVideoDados import YtVideoDados

# --noauth_local_webserver
print("Begin process...\n{}".format(datetime.now().time()))

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
    two_days_ago = datetime.now().date() - timedelta(days=2)
    
    Respiro()
        
    # channel_id = channels_list_response["items"][0]["id"]
    channel_id = confs.YtMarcaCanal.YtCanalId
    print(confs.YtMarcaCanal.YtCanalId)
    print(two_days_ago)
    resposta_canal = youtube_analytics.reports().query(
      ids="channel=={}".format(channel_id),
      metrics="annotationClicks,cardClicks,cardTeaserClicks,estimatedMinutesWatched,views,averageViewDuration,averageViewPercentage,likes,dislikes,shares,comments,videosAddedToPlaylists,videosRemovedFromPlaylists,subscribersGained,subscribersLost",
      dimensions="video",
      start_date=str(two_days_ago),
      end_date=str(two_days_ago),
      max_results=50,
      sort="-views"
    ).execute()

    respiro = respiro + 1
    if not resposta_canal or len(resposta_canal.get("rows", [])) == 0:
      return
    
    dias_reg = {"ini" : two_days_ago, "fim" : two_days_ago}
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
        
      Respiro()
      
      channel_id = confs.YtMarcaCanal.YtCanalId

      print(confs.YtMarcaCanal.YtCanalId)
      print(data_ini)

      resposta_canal = youtube_analytics.reports().query(
        ids="channel=={}".format(channel_id),
        metrics="annotationClicks,cardClicks,cardTeaserClicks,estimatedMinutesWatched,views,averageViewDuration,averageViewPercentage,likes,dislikes,shares,comments,videosAddedToPlaylists,videosRemovedFromPlaylists,subscribersGained,subscribersLost",
        dimensions="video",
        start_date=str(data_ini),
        end_date=str(data_ini),
        max_results=50,
        sort="-views"
      ).execute()
      
      respiro = respiro + 1
      if not resposta_canal or len(resposta_canal.get("rows", [])) == 0:
          data_ini = data_ini + timedelta(days=1)
          continue
      
      dias_reg = {"ini" : data_ini, "fim" : data_ini}
      Carga(confs, resposta_canal, dias_reg)
      data_ini = data_ini + timedelta(days=1)

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
  global respiro
  for registro in resposta_canal.get("rows", []):
    if not registro[0]:
        continue
    Respiro()
    video_response = youtube.videos().list(
      id=registro[0],
      part='snippet'
    ).execute()
    respiro = respiro + 1
    publicado = datetime.now();
    # Add each result to the list, and then display the list of matching videos.
    for video_result in video_response.get("items", []):
      publicado = str(video_result["snippet"]["publishedAt"]).replace('T', ' ').replace('Z', '')
      break
    item = YtVideoDados()
    item.YtConfigId = confs.Id
    
    item.StrId = registro[0]
    item.AnnotationClicks = registro[1]
    item.CardClicks = registro[2]
    item.CardTeaserClicks = registro[3]
    item.EstimatedMinutesWatched = registro[4]
    item.Views = registro[5]
    item.AverageViewDuration = registro[6]
    item.AverageViewPercentage = registro[7]
    item.Likes = registro[8]
    item.Dislikes = registro[9]
    item.Shares = registro[10]
    item.Comments = registro[11]
    item.VideosAddedToPlaylists = registro[12]
    item.VideosRemovedFromPlaylists = registro[13]
    item.SubscribersGained = registro[14]
    item.SubscribersLost = registro[15]

    item.Published = publicado
    item.StartDate = dias_reg["ini"]
    item.EndDate = dias_reg["fim"]
    item.Actived = True
    item.UserCreated = 1

    listaItens.append(item)

  YtVideoDados().SalvarLista(listaItens)


for confs in listaConfig:

  # The CLIENT_SECRETS_FILE variable specifies the name of a file that contains
  # the OAuth 2.0 information for this application, including its client_id and
  # client_secret. You can acquire an OAuth 2.0 client ID and client secret from
  # the {{ Google Cloud Console }} at
  # {{ https://cloud.google.com/console }}.
  # Please ensure that you have enabled the YouTube Data and YouTube Analytics
  # APIs for your project.
  # For more information about using OAuth2 to access the YouTube Data API, see:
  #   https://developers.google.com/youtube/v3/guides/authentication
  # For more information about the client_secrets.json file format, see:
  #   https://developers.google.com/api-client-library/python/guide/aaa_client_secrets
  CLIENT_SECRETS_FILE = "credenciais/{}.json".format(confs.YtMarcaCanal.YtCanalId)

  # These OAuth 2.0 access scopes allow for read-only access to the authenticated
  # user's account for both YouTube Data API resources and YouTube Analytics Data.
  YOUTUBE_SCOPES = ["https://www.googleapis.com/auth/youtube.readonly",
    "https://www.googleapis.com/auth/yt-analytics.readonly"]
  YOUTUBE_API_SERVICE_NAME = "youtube"
  YOUTUBE_API_VERSION = "v3"
  YOUTUBE_ANALYTICS_API_SERVICE_NAME = "youtubeAnalytics"
  YOUTUBE_ANALYTICS_API_VERSION = "v1"

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

  flow = flow_from_clientsecrets(CLIENT_SECRETS_FILE,
    scope=" ".join(YOUTUBE_SCOPES),
    message=MISSING_CLIENT_SECRETS_MESSAGE)

  storage = Storage("%s-oauth2.json" % confs.YtMarcaCanal.YtCanalId)
  credentials = storage.get()

  if credentials is None or credentials.invalid:
    credentials = run_flow(flow, storage, args)

  http = credentials.authorize(httplib2.Http())

  youtube = build(YOUTUBE_API_SERVICE_NAME, YOUTUBE_API_VERSION,
    http=http)
  youtube_analytics = build(YOUTUBE_ANALYTICS_API_SERVICE_NAME,
    YOUTUBE_ANALYTICS_API_VERSION, http=http)
  #print(confs.YtMarcaCanal.YtCanal)
  #try

    # channel_id = get_channel_id(youtube)
    # run_analytics_report(youtube_analytics, channel_id, args)
  options = {
      1: ReportDiario,
      2: ReportMenosXDias,
      3: ReportIntervaloDiaADia,
      4: ReportIntervaloTotal
  }
  options[confs.TipoCargaId](confs)

  # except HttpError as e:
  #   print ("An HTTP error %d occurred:\n%s" % (e.resp.status, e.content))