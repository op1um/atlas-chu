<?php
    namespace App\Controller;

    use Doctrine\DBAL\DriverManager;
    use Doctrine\DBAL\Driver\Connection;
    use Doctrine\DBAL\Driver\Statement;
    use App\Repository\EncounterRepository;
    use App\Repository\NurseUnitRepository;
    use App\Repository\LogsRepository;
    use App\Repository\CodeValueRepository;
    use Symfony\Component\HttpFoundation\Response;
    use Symfony\Component\HttpFoundation\Request;
    use Symfony\Component\Routing\Annotation\Route;
    use Sensio\Bundle\FrameworkExtraBundle\Configuration\Method;
    use Symfony\Bundle\FrameworkBundle\Controller\Controller;
    use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

    class ListeParamedicauxController extends Controller {
        /**
         * @Route("/", name="accueil")
         */
        public function index(Request $request, Connection $connection) {
            $userId = $request->query->get('user_id');
            return $this->render('listeParamedicaux/index.html.twig', [
                'userId' => $userId
            ]);
        }

        /**
         * @Route("/loadParamedList", name="loadParamedList")
         */
        public function loadParamedList(Request $request) {

            $encounterRepository = new EncounterRepository();
            $nurseUnitRepository = new NurseUnitRepository();
            $logsRepository = new LogsRepository();
            $listeUs = $request->request->get('nurseUnits');
            $nameListe = $request->request->get('listeParamed');
            $userId = $request->request->get('userId');
            $result = array();
            $preloadedUs = array();
            $regularUs = array();
            $listeParamedFinale = array();
            $nbJour = 70;
            $connection = $this->getDoctrine()->getConnection('default');
            $logsConnection = $this->getDoctrine()->getConnection('logs_db');
            
            switch($nameListe){
                case 'diet':
                    $idListe = 1;
                    $activityType = array(9989871, 9989877);
                    $activitySsType = array(11258977, 11221904);
                    $title = "Liste paramédicale diététicienne";
                break;
                case 'kine':
                    $idListe = 2;
                    $activityType = array(9569023);
                    $activitySsType = array();
                    $title = "Liste paramédicale kinésithérapeute";
                break;
                case 'orth':
                    $idListe = 3;
                    $activityType = array(10749288);
                    $activitySsType = array();
                    $title = "Liste paramédicale orthophoniste";
                break;
                case 'ergo':
                    $idListe = 4;
                    $activityType = array(10749272);
                    $activitySsType = array();
                    $title = "Liste paramédicale ergothérapeute";
                break;
                case 'podo':
                    $idListe = 5;
                    $activityType = array(11352160);
                    $activitySsType = array();
                    $title = "Liste paramédicale pédicure - podologue";
                break;
                case 'educ':
                    $idListe = 6;
                    $activityType = array(10749290);
                    $activitySsType = array();
                    $title = "Liste paramédicale éducateur sportif";
                break;
                case 'psyc':
                    $idListe = 7;
                    $activityType = array(23725363);
                    $activitySsType = array();
                    $title = "Liste paramédicale psychomotricien";
                break;
                case 'side':
                    $idListe = 8;
                    $activityType = array(32548103);
                    $activitySsType = array();
                    $title = "Liste paramédicale soins infirmiers";
                break;
                case 'stom':
                    $idListe = 9;
                    $activityType = array(34284305);
                    $activitySsType = array();
                    $title = "Liste paramédicale stomathérapeute";
                break;
                default:
                    $idListe = 1;
                    $activityType = array(9989871, 9989877);
                    $activitySsType = array(11258977, 11221904);
                    $title = "Liste paramédicale diététicienne";
            }

            // Séparation des US préchargées et non-préchargées
            $listeNurseUnits = $nurseUnitRepository->findPreloadedNurseUnits($listeUs, $connection);

            foreach($listeNurseUnits as $nurseUnit) {
                if($nurseUnit['PRELOAD_IND'] == 1) {
                    $preloadedUs[] = $nurseUnit['US_ID'];
                } 
                else
                {
                    $regularUs[] = $nurseUnit['US_ID'];
                }
            }

            $resultCreateLog = $logsRepository->createLog($userId, $idListe, $nameListe, $listeUs, $logsConnection);
            $resultLastLogId = $logsRepository->getLastInsertedLog($userId, $idListe, $listeUs, $logsConnection);

            foreach($resultLastLogId as $key => $value) {
                $logId = $value['LOGID']; 
            }

            if(count($preloadedUs) != 0) {
                $listePreloadedParamed = $encounterRepository->findPreloadedListeParamed($idListe, $preloadedUs, $connection);
                $listeDeltaParamed = $encounterRepository->findDeltaListeParamed($activityType, $activitySsType, $preloadedUs, $connection);
                $listeParamedFinale = $listePreloadedParamed + $listeDeltaParamed;
            }
            if(count($regularUs) != 0) {
                $listeRegularParamed = $encounterRepository->findAllListeParamedicaux($activityType, $activitySsType, $regularUs, $nbJour, $connection);
                $listeParamedFinale = $listeParamedFinale + $listeRegularParamed;
            }

            $result['logId'] = $logsRepository->updateLog($logId, $logsConnection);
            $result['data'] = $listeParamedFinale;
            $result['pageTitle'] = $title;

            $response = new Response(json_encode($result));
            $response->headers->set('Content-Type', 'application/json');

            return $response;
        }

        /**
         * @Route("/loadFacilities", name="loadFacilities")
         */
        public function loadFacilities(Request $request, Connection $connection)
        {
            $nurseUnitRepository = new NurseUnitRepository();
            $listFacilities = array();
            $facilities = $nurseUnitRepository->findAllFacilities($connection);

            foreach($facilities as $key => $value) {
                $listFacilities[$key]['title'] = $value['F_LABEL'];
                $listFacilities[$key]['key'] = $value['F_ID'];
                $listFacilities[$key]['folder'] = 'true';
                $listFacilities[$key]['lazy'] = 'true';
                $listFacilities[$key]['nodeLevel'] = 'facility';
            }

            $response = new Response(json_encode($listFacilities));
            $response->headers->set('Content-Type', 'application/json');
        
            return $response;
        }

        /**
         * @Route("/loadBuildingsAndLocations", name="loadBuildingsAndLocations")
         */
        public function loadBuildingsAndLocations(Request $request, Connection $connection)
        {
            $nurseUnitRepository = new NurseUnitRepository();
            $nodeType = $request->request->get('nodeType');
            $idParent = $request->request->get('parent');
            $listChildren = array();

            switch ($nodeType){
                case 'facility':
                    $buildings = $nurseUnitRepository->findAllBuildings($connection, $idParent);
                    foreach($buildings as $key => $value) {
                        $listChildren[$key]['title'] = $value['B_LABEL'];
                        $listChildren[$key]['key'] = $value['B_ID'];
                        $listChildren[$key]['folder'] = 'true';
                        $listChildren[$key]['lazy'] = 'true';
                        $listChildren[$key]['nodeLevel'] = 'building';
                    }
                    break;
                case 'building':
                    $locations = $nurseUnitRepository->findAllLocations($connection, $idParent);
                    foreach($locations as $key => $value) {
                        $listChildren[$key]['title'] = $value['L_LABEL'];
                        $listChildren[$key]['key'] = $value['L_ID'];
                        $listChildren[$key]['checkbox'] = 'true';
                        $listChildren[$key]['nodeLevel'] = 'location';
                    }
                    break;
                default:
                    $listChildren[0]['title'] = "Erreur de chargement";
                    $listChildren[0]['key'] = "0";
                    $listChildren[0]['unselectable'] = 'true';
                    $listChildren[0]['nodeLevel'] = 'error';
                            
            }

            $response = new Response(json_encode($listChildren));
            $response->headers->set('Content-Type', 'application/json');
        
            return $response;
        }

    }
