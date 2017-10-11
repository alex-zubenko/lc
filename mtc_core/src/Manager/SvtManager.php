<?php

/**
 * @file
 * Contains \Drupal\mtc_core\ManagerSvtManager.
 */
namespace Drupal\mtc_core\Manager;

use Drupal\Core\Database\Database;
use Drupal\user\PrivateTempStoreFactory;

/**
 * Class SvtManager.
 *
 * @package Drupal\mtc_core
 */
class SvtManager
{

    /*
     * @var array
     * @label('Correspondance of svt prefix to full name ')
     */
    protected $programNames = [
        'CA' => [
            'name' => 'Comportement alimentaire',
            'bg_color_point' => '#A7C255'
        ],
        'CNT' => [
            'name' => 'Carnet'
        ],
        'EMO' => [
            'name' => 'Emotion',
            'bg_color_point' => '#F89ED3'
        ],
        'PC' => [
            'name' => 'Pleine conscience',
            'bg_color_point' => '#B677D1'
        ],
        'FOR' => [
            'name' => 'Forme',
            'bg_color_point' => '#5CA3BA'
        ],
        'MOT' => [
            'name' => 'Motivation'
        ]
    ];

    /*
     * @var int current uid
     */
    protected $uid = null;

    /*
     * @var int usersub of uid
     */
    protected $userSub = null;

    /*
     * @var svt frontofffice request
     */
    protected $svtUrlFrontRequest = null;

    /*
     * @var svt cookie domain
     */
    protected $svtCookieDomain = null;

    /*
     * @var svt http ident
     */
    protected $svtHttpIdent = null;

    public function __construct(PrivateTempStoreFactory $storeFactory)
    {
        $this->uid = \Drupal::currentUser()->id();
        $this->store = $storeFactory->get('mtc_core');
        $lcConfig = \Drupal::service('mtc_core.config')->get('site');
        $this->svtUrlFrontRequest = $lcConfig['svt_url_front_quest'];
        $this->svtHttpIdent = $this->getBasicAuthHeader($lcConfig['svt_http']);
        $this->svtCookieDomain = $lcConfig['svt_cookie_domain'];
        $this->setUserSub();
        $this->setSvtSessionKey();
        $this->setD6CookieSession();
        if (empty($this->svtUrlFrontRequest)) {
            throw new \Exception('Le configuration svt front office request manque :http://www.linecoaching.com/meta-coaching/frontOffice/quest');
        }
    }

    /*
     * Function that makes http calls to svt server
     * @param array $params
     * @return SimpleXMLElement $response
     */
    public function requestToSvt($params)
    {
        if (empty($this->uid) || empty($this->userSub))
            return false;
        //
        // if empty session redirect to prog,start session
        $defaults = [
            'version' => '0.0',
            'action' => false,
            'ressource' => false,
            'heventid' => 0,
            'peventid' => 0,
            'body' => '',
            'service' => false
        ];

        $opts = array_merge($defaults, $params);

        $xml = '<?xml version="1.0" encoding="UTF-8"?>';
        $xml .= '<mtcmsg type="request">';
        $xml .= " <version>" . $opts['version'] . "</version>";
        $xml .= " <userid>" . session_id() . "</userid>";
        $xml .= " <subscription>" . $this->userSub->subid . "</subscription>";
        $xml .= '    <service name="frontoffice">';
        $xml .= " <action>" . $opts['action'] . "</action>";
        if (! empty($opts['ressource']))
            $xml .= "        <ressource>" . $opts['ressource'] . "</ressource>";
        if (! empty($opts['service']))
            $xml .= "        " . $opts['service'];
        $xml .= '    </service>';
        $xml .= '    <body>';
        $xml .= "        " . $opts['body'];
        $xml .= '    </body>';
        $xml .= '</mtcmsg>';

        $ch = curl_init();
        $headers = [];
        $headers[] = 'Accept: application/xml';
        $headers[] = 'Content-Type: application/xml';
        if ($this->svtHttpIdent) {
            $headers[] = $this->svtHttpIdent;
        }
        curl_setopt($ch, CURLOPT_HTTPHEADER, $headers);
        curl_setopt($ch, CURLOPT_URL, $this->svtUrlFrontRequest);
        curl_setopt($ch, CURLOPT_POST, 1);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        $response = curl_exec($ch);
        $httpcode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        if ($httpcode == "200") {
            $response = simplexml_load_string($response);
            if ($response->service->status[0] == 0) {
                return $response;
            }
        }
        return null;
    }

    /*
     * Function that obtains information from svt for sidebar
     */
    public function svtProgSidebarClient()
    {
        // @todo move params
        $param = [
            'action' => 'list',
            'ressource' => 'module_info',
            'service' => '<moduleid>3210</moduleid>'
        ];
        $svtAlimentaire = $this->requestToSvt($param);
        // load parcours entretiens forme
        $param = [
            'action' => 'list',
            'ressource' => 'module_info',
            'service' => '<moduleid>3211</moduleid>'
        ];
        $svtForm = $this->requestToSvt($param);
        // load tools
        $param = [
            'action' => 'list',
            'ressource' => 'tool-category',
            'body' => "
        <category code='OUT_CNT'></category>
        <category code='OUT_CA'></category>
        <category code='OUT_EMO'></category>
        <category code='OUT_PC'></category>
        <category code='OUT_COU'></category>"
        ];
        $svtTools = $this->requestToSvt($param);
        $svtToolsCategoryList = [];
        if ($svtTools && is_array($svtTools->body->category)) {
            foreach ($svtTools->body->category as $category) {
                $category = (array) $category;
                $svtToolsCategoryList[] = $category[0];
            }
        }
        // obtain favourite tools list
        $progFavouriteToolList = $this->getFavouriteToolsList();
        return compact('svtForm', 'svtAlimentaire', 'svtToolsCategoryList', 'progFavouriteToolList');
    }

    /*
     * Function that obtains information from svt for home page client
     */
    public function svtHomeClient()
    {
        // @todo move paramsif (!$this->store->get('svtSync')) {
        $param = [
            'action' => 'list',
            'ressource' => 'module_info',
            'service' => '<moduleid>3210</moduleid>'
        ];
        $svtAlimentaire = $this->requestToSvt($param);
        // @todo, logic from d6,to remove once upgraded
        if ($svtAlimentaire) {
            $svtAlimentaire->moduleAliasName = $this->getModuleAliasName($svtAlimentaire->body->module->name);
        }
        // load parcours entretiens forme
        $param = [
            'action' => 'list',
            'ressource' => 'module_info',
            'service' => '<moduleid>3211</moduleid>'
        ];
        $svtForm = $this->requestToSvt($param);
        // @todo, logic from d6,to remove once upgraded
        if ($svtForm) {
            $svtForm->moduleAliasName = $this->getModuleAliasName($svtForm->body->module->name);
        }

        // new-defis
        $param = [
            'action' => 'list',
            'ressource' => 'new-defis',
            'body' => "<number>0</number>"
        ];
        $svtNewDefis = $this->requestToSvt($param);
        $svtNewDefis = $this->formatSvtDefiReponse($svtNewDefis);
        // started defis
        $param = [
            'action' => 'list',
            'ressource' => 'started-defis',
            'body' => "<number>0</number>"
        ];
        $svtStartedDefis = $this->requestToSvt($param);
        $svtStartedDefis = $this->formatSvtDefiReponse($svtStartedDefis);
        return compact('svtNewDefis', 'svtStartedDefis', 'svtForm', 'svtAlimentaire');
    }

    /*
     * Function that format svt data
     * @param SimpleXMLElement $svtDefis
     * @return Array
     */
    public function formatSvtDefiReponse($svtDefis)
    {
        if (empty($svtDefis)) {
            return false;
        }
        $result = [];
        $defis = [];
        $points = $svtDefis->body->userpoints[0];
        foreach ($svtDefis->body->defi as $defi) {
            $defis[(string) $defi->attributes()->id] = array(
                'date' => $defi->enddate[0],
                'nbjours' => $defi->nbjours[0]
            );
        }
        Database::setActiveConnection('d6-int-prog');
        $connection = Database::getConnection();
        foreach ($defis as $id => $defi) {
            // connect to d6,obtain usersub & session id
            $node = $connection->query(" SELECT * FROM node node
                    LEFT JOIN node_revisions rev ON rev.vid = node.vid
                    LEFT JOIN content_type_widget_defis node_data ON node.vid = node_data.vid
                    WHERE node_data.field_widget_defi_id_value = :id AND node.status = 1;", [
                ':id' => $id
            ])->fetchObject();
            $node->prog_name = (object) $this->programNames[$node->field_widget_defi_theme_value];
            $result[$id]['defis'] = $defi;
            $result[$id]['node'] = $node;
        }
        Database::setActiveConnection();
        return $result;
    }

    /**
     * Function that sets the sid from drupal 6 used by drupal 6 prog to make calls
     */
    public function setD6CookieSession()
    {
        setcookie('mtc-svt', session_id(), 0, '/', $this->svtCookieDomain);
    }

    /**
     * Function that sets the sid from drupal 6 used by drupal 6 prog to make calls
     */
    public function setSvtSessionKey()
    {
      //  if (!$this->store->get('svtSync')) {
            Database::setActiveConnection('d6-int-svt');
            try {
                db_merge('hperson')->key([
                    'hpe_drupal_id' => $this->uid
                ])
                    ->fields([
                    'hpe_drupal_sid' => session_id()
                ])
                    ->execute();
            } catch (\Exception $e) {
                $message = $e->getMessage();
                \Drupal::logger('mtc_core')->error($message);
            }
            Database::setActiveConnection();
            $this->store->set('svtSync', true);
       // }
    }

    /**
     * Function that obtains the usersub
     */
    public function setUserSub()
    {
        if ($this->store->get('userSub')) {
            $this->userSub = $this->store->get('userSub');
            return true;
        }
        // @todo ,connect to d8 tables
        Database::setActiveConnection('d6-int-drupal');
        $connection = Database::getConnection();
        // @todo, when migrated get data from d8 instead of d6
        $userSub = $connection->query("SELECT subid FROM mc_wsubscription WHERE uid =:uid
                                       AND etat = 1 AND progid =1", [
            ':uid' => $this->uid
        ])->fetchObject();
        // revert to normal d8
        Database::setActiveConnection();
        if (! $userSub) {
            $userSub = Database::getConnection()->query("SELECT subid FROM mc_wsubscription WHERE uid =:uid
                                       AND etat = 1 AND progid =1", [
                ':uid' => $this->uid
            ])->fetchObject();
        }
        Database::setActiveConnection();
        $this->userSub = $userSub;
        $this->store->set('userSub', $this->userSub);
        return true;
    }

    /**
     * Function that obtains the usersub
     */
    public function getFavouriteToolsList()
    {
        // @todo ,connect to d6 tables
        $toolList = [];
        Database::setActiveConnection('d6-int-drupal');
        $connection = Database::getConnection();
        // @todo, when migrated get data from d8 instead of d6
        $toolListQuery = $connection->query("SELECT * FROM mc_outils_favoris WHERE subid =:subid  ORDER
                                             BY num_out_fav ASC LIMIT 0 , 4", [
            ':subid' => $this->userSub->subid
        ])->fetchAll(\PDO::FETCH_OBJ);
        Database::setActiveConnection('d6-int-prog');
        $connection = Database::getConnection();

        foreach ($toolListQuery as $toolRecord) {
            $nodeDetails = $connection->query("SELECT * FROM mc_wpagetext mcwp
                                                 LEFT JOIN   node n  ON n.nid = mcwp.widget_nid
                                                 LEFT JOIN content_field_tool1_phrase_intro cftpi ON  n.nid = cftpi.nid
                                                 WHERE mcwp.ctask=:ctask
                                                 AND mcwp.widget_name IN ('widget_redac_mctxt_poids', 'widget_redac_mctxtp_tool1', 'widget_redac_mctxtp_tool2')", [
                ':ctask' => $toolRecord->ctask
            ])->fetchObject();
            list ($null, $codeTheme) = explode('_', $toolRecord->ctask);
            $tool = $toolRecord;
            $tool->title = isset($nodeDetails->ntask) ? $nodeDetails->ntask : $nodeDetails->field_tool1_phrase_intro_value;
            $tool->codeTheme = strtolower($codeTheme);
            $toolList[] = $tool;
        }
        // revert to normal d8
        Database::setActiveConnection();
        return $toolList;
    }

    /**
     * Function that obtains module alias,base on d6 logic
     *
     * @todo , remove once prog is migrated,illogical
     * @param str $moduleName
     * @return string
     */
    public function getModuleAliasName($moduleName)
    {
        $alias = '';
        $strExploded = explode(" ", $moduleName);
        $cnt = count($strExploded);
        if (! isset($strExploded[1]))
            return $alias;
        return ($cnt > 2) ? strtoupper($strExploded[1][0] . $strExploded[2][0]) : strtoupper(substr($strExploded[1], 0, 3));
    }

    /**
     * Function that return basic auth array
     *
     * @param array $svtIdent
     * @return array
     */
    public function getBasicAuthHeader($svtIdent = [])
    {
        if (empty($svtIdent['user']) || empty($svtIdent['password'])) {
            return null;
        }
        $code = base64_encode($svtIdent['user'] . ':' . $svtIdent['password']);
        return 'Authorization:Basic ' . $code;
    }

    public function clean()
    {
        $this->store->delete('userSub');
        $this->store->delete('svtSync');
    }
}
