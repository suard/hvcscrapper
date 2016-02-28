<?php namespace Suard\HvcScrapper\Tests;

use Suard\HvcScrapper;
use Suard\HvcScrapper\Models;

class HvcScrapperTest extends \PHPUnit_Framework_TestCase
{
    /**
     *
     * @dataProvider validParamsProvider
     */
    public function testHvcParamsInstance($postalcode, $houseno)
    {
        $hvcparams = new Models\HvcParams($postalcode, $houseno);
        $this->assertInstanceOf('Suard\HvcScrapper\Models\HvcParams', $hvcparams);
    }

    /**
     *
     * @dataProvider validParamsProvider
     */
    public function testHvcScrapperInstance($postalcode, $houseno)
    {
        $hvcscrapper = new HvcScrapper\HvcScrapper(new Models\HvcParams($postalcode, $houseno));
        $this->assertInstanceOf('Suard\HvcScrapper\HvcScrapper', $hvcscrapper);
    }

    /**
     *
     * @dataProvider validParamsProvider
     */
    public function testHvcParamsValid($postalcode, $houseno)
    {
        $hvcscrapper = new HvcScrapper\HvcScrapper(new Models\HvcParams($postalcode, $houseno));
        $this->assertGreaterThan(0, count(json_decode($hvcscrapper->scrap())));
    }

    /**
     *
     * @dataProvider invalidParamsProvider
     * @expectedException \Exception
     */
    public function testHvcParamsInvalid($postalcode, $houseno)
    {
        $hvcscrapper = new HvcScrapper\HvcScrapper(new Models\HvcParams($postalcode, $houseno));
    }

    /**
     *
     */
    public function testScrapCalendarItems1()
    {
        $hvcscrapper = new HvcScrapper\HvcScrapper(new Models\HvcParams('1687BE', '2')); // Medemblik
        $results = json_decode($hvcscrapper->scrap());

        // test 1
        $this->assertEquals($results[0]->type, 'plastic');
        $this->assertEquals(date('Y-m-d', strtotime($results[0]->date->date)), '2016-01-18');

        // test 2
        $this->assertEquals($results[31]->type, 'rest');
        $this->assertEquals(date('Y-m-d', strtotime($results[31]->date->date)), '2016-06-29');

        // test 3
        $this->assertEquals($results[49]->type, 'plastic');
        $this->assertEquals(date('Y-m-d', strtotime($results[49]->date->date)), '2016-10-24');
    }

    public function validParamsProvider()
    {
        return [
            ['3328BG', '100'],
            ['3311vc', '18 ']
        ];
    }

   public function invalidParamsProvider()
   {
       return [
           ['3328 BG', '100'],
           ['3311vc', 'AA']
       ];
   }

    public function scrapProvider()
    {
        return ['1687BE', '2']; // medemblik
    }


}