<?php
namespace Drupal\mtc_core\Controller;

use Drupal\Core\Controller\ControllerBase;
use Symfony\Component\HttpFoundation\Response;
use Solarium\Core\Client\Client;
use Drupal\search_api\Query\ResultSet;

/**
 * Class LcSearchController.
 *
 * @package Drupal\mtc_core\Controller
 */
class LcSearchController extends ControllerBase
{

    // translation bettween query string and solr fields
    protected $solrMap = [
        'query' => 'spell',
        'filter_format' => 'itm_field_tag_transverse_format',
        'filter_theme' => 'itm_field_theme',
        'filter_type' => 'ts_type'
    ];

    protected $solrConfig;

    protected $limitPager = 10;

    /**
     * Constructor.
     */
    public function __construct()
    {
        $sorlConf= \Drupal::config('search_api.server.mtc_core')->get('backend_config');
        $solrConnector = $sorlConf['connector_config'];
        if (empty($solrConnector)) {
            throw new \Exception('Missing sorl configuration');
        }
        $this->solrConfig = [
            'endpoint' => [
                'localhost' => [
                    'host' => $solrConnector['username'] . ':' . $solrConnector['password'] . '@' . $solrConnector['host'],
                    'port' => $solrConnector['port'],
                    'path' => $solrConnector['path'],
                    'core' => $solrConnector['core'],
                    'scheme' => $solrConnector['scheme']
                ]
            ]
        ];
    }

    /**
     * Entry point controller.
     *
     * @return string Return Hello string.
     */
    public function indexAction()
    {
        $queryParams = \Drupal::request()->query->all();
        if (empty($queryParams)) {
            return [
                '#markup' => '',
                '#content' => []
            ];
        }
        $client = new Client($this->solrConfig);
        // test connection
        // create a ping query
        $ping = $client->createPing();
        // execute the ping query
        try {
            $client->ping($ping);
        } catch (\Exception $e) {
            $message = $e->getMessage();
            \Drupal::logger('mtc_core')->error($message);
            return [
                '#markup' => '<div class="alert alert-warning">Le moteur de recherche est à actuellement indisponible</div>',
                '#content' => []
            ];
        }
        // create query
        $query = $client->createSelect();
        // add highlighting
        $hl = $query->getHighlighting();
        $hl->setFields('spell');
        $hl->setSimplePrefix('<span class="highlight-seach-result">');
        $hl->setSimplePostfix('</span>');
        // construct general search text
        if (! empty($queryParams['query'])) {
//          $queryParams['query'] = str_replace(' ', '+', trim($queryParams['query']));
          $queryParams['query'] = '"'. $queryParams['query'] . '"';
          $q = $this->solrMap['query'] . ':' . trim($queryParams['query']);
          $query->setQuery($q);
        }
        // tag format
        if (! empty($queryParams['filter_format']) && is_array($queryParams['filter_format'])) {
            $q = '( ' . implode(' OR ', $queryParams['filter_format']) . ' )';
            $q = $this->solrMap['filter_format'] . ':' . $q;
            $query->setQuery($q);
        }
        // field theme
        if (! empty($queryParams['filter_theme']) && is_array($queryParams['filter_theme'])) {
            $q = '( ' . implode(' OR ', $queryParams['filter_theme']) . ' )';
            $q = $this->solrMap['filter_theme'] . ':' . $q;
            $query->setQuery($q);
        }
        // field type
        if (! empty($queryParams['filter_type']) && is_array($queryParams['filter_type'])) {
            $q = '( ' . implode(' OR ', $queryParams['filter_type']) . ' )';
            $q = $this->solrMap['filter_type'] . ':' . $q;
            $query->setQuery($q);
        }
        // init pager and offset
        $page = pager_find_page();
        $offset = $this->limitPager * $page;
        $query->setStart($offset);
        $query->setRows($this->limitPager);

        // this executes the query and returns the result
        $resultSet = $client->select($query);
        $data = $resultSet->getData();
        // init pager
        $page = pager_default_initialize($data['response']['numFound'], $this->limitPager);
        $render = [];
        if ($resultSet) {
            $render[] = [
                '#theme' => 'lc_search_result',
                '#content' => $data,
                '#query' => $queryParams['query'],
                '#numFound' => $data['response']['numFound'],
            ];
            // add the pager.
            $render[] = [
                '#type' => 'pager'
            ];
        }
        return $render;
    }
}
