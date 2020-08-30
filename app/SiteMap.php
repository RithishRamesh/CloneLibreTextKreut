<?php

namespace App;

use App\Question;
use Illuminate\Database\Eloquent\Model;
use GuzzleHttp\Client;

use Illuminate\Support\Facades\DB;


class SiteMap extends Model
{
    protected $tags;
    protected $questionIds;
    protected $technologyIds;
    protected $client;
    protected $tokens;

    public function __construct(array $attributes = [])
    {
        //parent::__construct($attributes);


        $this->client = new Client();
        $this->tokens = $this->getTokens();

    }

    public function init()
    {


        $sitemaps = $this->getSiteMaps();
        foreach ($sitemaps as $sitemap) {
            echo $sitemap . "\r\n";
            $this->iterateSiteMap($sitemap);
        }


    }

    public function getTokens()
    {
        $response = $this->client->get('https://files.libretexts.org/authenBrowser.json');
        return json_decode($response->getBody());


    }

    public function isValidAssessment($loc)
    {
        $validPaths = ['https://query.libretexts.org/Assessment_Gallery/H5P_Assessments/',
            'https://query.libretexts.org/Assessment_Gallery/IMathAS_Assessments/',
            'https://query.libretexts.org/Assessment_Gallery/WeBWorK_Assessments/',
            'https://query.libretexts.org?title=Assessment_Gallery/'];

        foreach ($validPaths as $path)
            if (strpos($loc, $path) === 0) {
                return true;
            }
        return false;
    }

    public function iterateSiteMap($sitemap)
    {
        $response = $this->client->get($sitemap);
        $xml = simplexml_load_string($response->getBody());

        $num = 0;
        $time = time();
        $calls = 0;
        foreach ($xml->url as $value) {

            $loc = $value->loc[0];
            if ($this->isValidAssessment($loc)) {
                //file_put_contents('sitemap', "$loc  $num \r\n", FILE_APPEND);
                $this->getLocInfo($loc);
                if ($calls === 1){
                    echo 'time';
                    time_sleep_until($time+1);
                    $time = time();
                    $calls = 0;
                }
                $calls ++;
                $num++;
                if ($num >6) {return;}
            }

        }


    }

    public function getLocInfo($url)

    {

        $host = parse_url($url)['host'];
        $path = substr(parse_url($url)['path'], 1);//get rid of trailing slash

        $library = str_replace('.libretexts.org', '', $host);
        $tokens = $this->tokens;
        $token = $tokens->{$library};
        $headers = ['Origin' => 'https://adapt.libretexts.org', 'x-deki-token' => $token];

        $final_url = "https://$library.libretexts.org/@api/deki/pages/=" . urlencode($path) . '?dream.out.format=json';

        try {
            $response = $this->client->get($final_url, ['headers' => $headers]);
            $page_info = json_decode($response->getBody(), true);

            $technology_id = $page_info['@id'];
            //file_put_contents('sitemap', "$final_url $technology_id \r\n", FILE_APPEND);
            $tags = [];
            if ($page_info['tags']['tag']) {
                foreach ($page_info['tags']['tag'] as $key => $value) {
                    $tag = $value['@value'];
                    if (strpos($tag, 'tech:') === 0) {
                        $technology = str_replace('tech:', '', $tag);
                    } else {
                        $tags[] = strtolower($tag);
                    }
                }
            }

            $question = Question::firstOrCreate(['technology_id' => $technology_id, 'technology' => $technology]);
            $Question = new Question;

            if ($tags) {
                foreach ($tags as $key => $tag) {
                    $Question->addTag($tag, mb_strtolower($tag), $question);
                }
            }


        } catch (Exception $e) {
            echo $e->getMessage();
        }


    }

    public function getSiteMaps()
    {

        $response = $this->client->get('https://query.libretexts.org/sitemap.xml');
        $xml = simplexml_load_string($response->getBody());
        $key = 0;
        $sitemaps = [];
        foreach ($xml->sitemap as $value) {
            $sitemaps[$key] = (string)$xml->sitemap[$key]->loc[0];
            $key++;
        }
        return $sitemaps;
    }


}
