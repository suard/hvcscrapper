<?php namespace Suard\HvcScrapper;

use Suard\HvcScrapper\Models;
use Carbon\Carbon;

/**
 * Class that scraps the HVC Calendar page. HVC is a company that collects
 * trash in The Netherlands.
 *
 * @author Suard Damme <suardd@gmail.com>
 * @license MIT, https://opensource.org/licenses/MIT
 */
class HvcScrapper
{
    /**
     * The start url
     *
     * @var string
     */
    private $hvcUrl = 'https://inzamelkalender.hvcgroep.nl/';

    /**
     * Array of possible urls which are not correct
     *
     * @var array
     */
    private $hvcFalseUrls = ['https://inzamelkalender.hvcgroep.nl/geen_inzameling'];

    /**
     * Object of HvcParam
     *
     * @var HvcParams
     */
    private $hvcParams;

    /**
     * The period to get the calendar from. Currently only 'year' is supported.
     *
     * @var string
     */
    private $period = 'year';

    /**
     * Creates the HvcScrapper object with the HvcParams object.
     *
     * @param Models\HvcParams $hvcParams
     */
    public function __construct(Models\HvcParams $hvcParams)
    {
        $this->hvcParams = $hvcParams;
    }

    /**
     * Scraps the trash calendar items and return them
     *
     * @return string
     */
    public function scrap()
    {
        $items = [];
        $crawler = $this->scrapHvc();
        $i = 1;

        $crawler->filter('div[id=content] table')->each(function ($node) use (&$i, &$items) {
            // the first 12 tables are the months
            if ($i > 12) {
                return;
            }

            // loop through the columns
            $node->filter('tr td')->each(function ($colNode) use (&$i, &$items) {
                // check if the node must get scrapped
                if (!$this->getHvcWeek($colNode->attr('header')) || $colNode->attr('class') == 'inactive') {
                    return;
                }

                // first set of result
                $date = $this->formatAfvalDate(date('Y'), $i, trim($colNode->text()));
                // get the collection days if available
                $items = array_merge($items, $this->scrapAfvalItems($colNode, $date));
            });

            // count one month
            $i++;
        });

        return json_encode($items);
    }

    /**
     * Start the goutte client and scraps the hvc pages.
     *
     * @return \Symfony\Component\DomCrawler\Crawler
     * @throws \Exception
     */
    private function scrapHvc()
    {
        // first get the main page and submit form with params
        $client = $this->createGoutteClient();
        $crawler = $client->request('GET', $this->hvcUrl);

        // submit the form
        $form = $crawler->filter('form[id=woning]')->form();
        $form->setValues(['postcode' => $this->formatPostalcode($this->hvcParams->postalcode), 'huisnummer' => $this->hvcParams->houseno]);
        $crawler = $client->submit($form);

        // check if postalcode has trash collections
        if (in_array($client->getHistory()->current()->getUri(), $this->hvcFalseUrls)) {
            throw new \Exception(sprintf('The postalcode %s with house number %s gives no results', $this->hvcParams->postalcode, $this->hvcParams->houseno));
        }

        // this is not fully being used
        if ($this->period === 'year') {
            // select the year
            $link = $crawler->selectLink('Jaar')->link();
            $crawler = $client->click($link);
        } else {
            throw new \Exception("Only year is allowed specifying the year variable");
        }

        return $crawler;
    }

    /**
     * Scrap the calendar collections items and return an array of items
     *
     * @param $node
     * @param $date
     * @return array
     */
    private function scrapAfvalItems($node, $date)
    {
        $items = [];
        $collections = $this->scrapTypes($node);

        foreach ($collections as $collection) {
            $item = new \stdClass();
            $item->postalcode = $this->hvcParams->postalcode;
            $item->houseno = $this->hvcParams->houseno;
            $item->date = $date;
            $item->type = strtolower($collection);
            $items[] = $item;
        }

        return $items;
    }

    /**
     * Scrap the trashcollections of a certain date in the HVC webpage.
     *
     * @param type $node
     * @return array
     */
    private function scrapTypes($node)
    {
        $types = [];

        $node->filterXPath('//a')->each(function ($trashNode) use (&$types) {
            $types[] = trim($trashNode->attr('title'));
        });

        return $types;
    }

    /**
     * Check if the header attribute of the column has the 'week_'.
     *
     * @param type $var
     * @return boolean
     */
    private function getHvcWeek($var)
    {
        if (strpos($var, 'week_') !== false) {
            return (int)str_replace('week_', '', $var);
        }

        return false;
    }

    /**
     * Returns a (Goutte) webscrap client with no SSL verifier.
     *
     * @return \Goutte\Client
     */
    private function createGoutteClient()
    {
        $client = new \Goutte\Client();
        $guzzleClient = new \GuzzleHttp\Client(array(
            'curl' => array(
                CURLOPT_SSL_VERIFYHOST => FALSE,
                CURLOPT_SSL_VERIFYPEER => FALSE
            ),
        ));
        $client->setClient($guzzleClient);

        return $client;
    }

    /**
     * Format the postalcode for service and returns it for further use.
     *
     * @todo check if this function is needed!
     * @param string $postalcode
     * @return mixed
     */
    private function formatPostalcode($postalcode)
    {
        try {
            return strtoupper(sprintf('%s%s', substr($postalcode, 0, 4), substr($postalcode, 4, 2)));
        } catch (Exception $ex) {
            return false;
        }
    }

    /**
     * Creates a Carobon Date formatting the day of the date first.
     *
     * @param type $year
     * @param type $month
     * @param type $day
     * @return \Carbon\Carbon
     */
    private function formatAfvalDate($year, $month, $day)
    {
        preg_match_all('!\d+!', $day, $matches);
        return Carbon::create($year, $month, implode('', $matches[0]), 0, 0, 0, 'Europe/Amsterdam');
    }
}
