<?php

if (!defined('DW_LF')) {
    define('DW_LF', "\n");
}

/**
 * Test the related plugin
 *
 * @group plugin_related
 * @group plugins
 */
class dokuwiki_plugin_related_test extends DokuWikiTest
{

    protected $pluginsEnabled = array('related');

    // Namespace where all test page will be added
    const TEST_PAGE_NAMESPACE = syntax_plugin_related::PLUGIN_NAME . ':';
    const REFERENT_PAGE_ID = self::TEST_PAGE_NAMESPACE . 'referent';
    public static $referrers = array();
    const DOKU_DATA_DIR = '/dokudata/pages';
    const DOKU_CACHE_DIR = '/dokudata/cache';
    const REFERRERS_COUNT = 4;

    // for the extra pattern test
    const EXTRA_PATTERN_VALUE = '{{backlinks>.}}';
    public static $extraPatternPage;

    // The value of the REFERRERS_ID_TOP is the referrers that will
    // got two backlinks and should therefore be on the top in a list of related page
    // This number should be less that REFERRERS_COUNT
    const REFERRERS_ID_TOP = 3;

    // Create the pages
    // and configure
    public static function setUpBeforeClass()
    {
        parent::setUpBeforeClass();
        // Config changes in function setUpBeforeClass() have no effect set setup

        // Create the pages
        $changeSummary = 'Test - plugin related';

        // Create the referent page
        $referentPageId = self::REFERENT_PAGE_ID;
        saveWikiText($referentPageId,
            '======  A referent page ====== ' . DW_LF . DW_LF .
            '=====  Articles Related ====== ' . DW_LF .
            '<' . syntax_plugin_related::PLUGIN_NAME . '>'
            , $changeSummary);
        idx_addPage($referentPageId);

        // Create the referrers page with a link to the referent page
        for ($i = 1; $i <= self::REFERRERS_COUNT; $i++) {
            self::createReferrerPage($referentPageId);
        }

        // Create a referrer page that links to the first referrers
        // It should then be the first one in the related list
        if (self::REFERRERS_ID_TOP > self::REFERRERS_COUNT){
            throw new Exception("The value of the REFERRERS_ID_TOP (".self::REFERRERS_ID_TOP.") should be less than the value of REFERRERS_COUNT (".self::REFERRERS_COUNT.")");
        }
        self::createReferrerPage(self::$referrers[self::REFERRERS_ID_TOP]);

        // Extra Pattern Page
        $PageId = 'extraPatternTest';
        self::$extraPatternPage = self::TEST_PAGE_NAMESPACE . $PageId;
        saveWikiText(self::$extraPatternPage,
            '======  '. $PageId .' ======' . DW_LF . DW_LF .
            self::EXTRA_PATTERN_VALUE. DW_LF . DW_LF .
            '<'.syntax_plugin_related::PLUGIN_NAME.'>', $changeSummary);
        idx_addPage(self::$extraPatternPage);
        self::createReferrerPage(self::$extraPatternPage);

        // A Home page to be able to test visually
        $startId = self::TEST_PAGE_NAMESPACE . 'start';
        $referrersWiki = "";
        foreach (self::$referrers as $referrer) {
            $referrersWiki .= '  * [[' . $referrer . ']]' . DW_LF;
        }
        saveWikiText($startId, '====== The related home page ======' . DW_LF . DW_LF .
            '  * [[' . $referentPageId . ']]' . DW_LF .
            $referrersWiki .
            '  * [[' . self::$extraPatternPage . ']]' . DW_LF
            , $changeSummary);


        dbglog("\nTest Plugin" . syntax_plugin_related::PLUGIN_NAME.': Start Page was created at ' . wikiFN($startId));


    }

    /**
     * @param $referentPageId - The Full referrant page id
     * @return string
     */
    public static function createReferrerPage($referentPageId): string
    {
        $referrerId = sizeof(self::$referrers)+1;
        $referrerPageId = self::TEST_PAGE_NAMESPACE . 'referrer' . $referrerId;
        saveWikiText($referrerPageId,
            '======   Referrer ' . $referrerId . ' to '.$referentPageId.' ======' . DW_LF . DW_LF .
            '  * [[' . $referentPageId . ']]', "Test");
        idx_addPage($referrerPageId);
        self::$referrers[]=$referrerPageId;
        return $referrerPageId;
    }

    public function setUp()
    {
        // Config changes have only effect in function setUpBeforeClass()
        global $conf;

        parent::setUp();


        $conf ['plugin'][syntax_plugin_related::PLUGIN_NAME][syntax_plugin_related::EXTRA_PATTERN_CONF] = self::EXTRA_PATTERN_VALUE;

        // Otherwise the page are created in a tmp dir
        // ie C:\Users\gerard\AppData\Local\Temp/dwtests-1550072121.2716/data/
        // and we cannot visualize them
        // This is not on the savedir conf value level because it has no effect on the datadir value
        $conf['datadir'] = getcwd() . self::DOKU_DATA_DIR;
        $conf['cachetime'] = -1;
        $conf['allowdebug'] = 1; // log in cachedir+debug.log
        $conf['cachedir'] = getcwd(). self::DOKU_CACHE_DIR;
        dbglog("\nSetup was called- Test Plugin" . syntax_plugin_related::PLUGIN_NAME);
    }

    /**
     * Control the plugin.info.txt
     */
    public function test_pluginInfoTxt()
    {
        $file = __DIR__ . '/../plugin.info.txt';
        $this->assertFileExists($file);

        $info = confToHash($file);

        $this->assertArrayHasKey('base', $info);
        $this->assertEquals(syntax_plugin_related::PLUGIN_NAME,$info['base']);

        $this->assertArrayHasKey('author', $info);
        $this->assertArrayHasKey('name', $info);
        $this->assertArrayHasKey('desc', $info);

        $this->assertArrayHasKey('date', $info);
        $this->assertRegExp('/^\d\d\d\d-\d\d-\d\d$/', $info['date']);
        $this->assertTrue(false !== strtotime($info['date']));


        $this->assertArrayHasKey('url', $info);
        $this->assertRegExp('/^https?:\/\//', $info['url']);

        $this->assertArrayHasKey('email', $info);
        $this->assertTrue(mail_isvalid($info['email']));


    }

    /**
     * test if the plugin is loaded.
     */
    public function test_plugin_isLoaded()
    {
        global $plugin_controller;
        $this->assertTrue(
            in_array(
                syntax_plugin_related::PLUGIN_NAME,
                $plugin_controller->getList()),
            syntax_plugin_related::PLUGIN_NAME . " plugin is loaded"
        );
    }

    // Test the dokuwiki backlinks function
    public function test_backlinks()
    {

        $backlinks = ft_backlinks(self::REFERENT_PAGE_ID);
        $this->assertEquals(self::REFERRERS_COUNT, sizeof($backlinks));

    }

    // Test the related features of the related function
    // default, max and order
    public function test_BaseRelated()
    {
        // Without max
        $referentPageId = self::REFERENT_PAGE_ID;
        $relatedPlugin = new syntax_plugin_related();
        // Without max, it will take the conf default (10)
        $referrers = $relatedPlugin->related($referentPageId);
        $this->assertEquals(self::REFERRERS_COUNT, sizeof($referrers));
        // The first one must be the one that had two backlinks
        $this->assertEquals(self::$referrers[self::REFERRERS_ID_TOP],$referrers[0][syntax_plugin_related::RELATED_PAGE_ID_PROP]);

        // With a max via argument
        $max = 1;
        $referrers = $relatedPlugin->related($referentPageId, $max);
        $expected = $max + 1; // +1 for the more page
        $this->assertEquals($expected, sizeof($referrers));

        // With a max via the conf
        global $conf;
        $oldMaxLinksValue = $conf ['plugin'][syntax_plugin_related::PLUGIN_NAME][syntax_plugin_related::MAX_LINKS_CONF];
        $conf ['plugin'][syntax_plugin_related::PLUGIN_NAME][syntax_plugin_related::MAX_LINKS_CONF] = $max;
        $referrers = $relatedPlugin->related($referentPageId);
        $this->assertEquals($expected, sizeof($referrers));
        $conf ['plugin'][syntax_plugin_related::PLUGIN_NAME][syntax_plugin_related::MAX_LINKS_CONF] = $oldMaxLinksValue;

    }

    public function test_extraPattern()
    {


        $request = new TestRequest();
        $request->get(array('id' => self::$extraPatternPage));
        $response = $request->execute();

        //$response->queryHTML('#'.syntax_plugin_related::ELEMENT_ID)->attr('content');
        $idElements = $response->queryHTML('#'.syntax_plugin_related::ELEMENT_ID)->length;
        $this->assertEquals(2,$idElements);

    }


}
