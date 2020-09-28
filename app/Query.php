<?php

namespace App;

use App\MindTouchEvent;
use App\Question;
use Illuminate\Database\Eloquent\Model;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;

use App\Traits\MindTouchTokens;

use App\Exceptions\Handler;
use \Exception;

class Query extends Model
{

    use MindTouchTokens;

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
        $this->library = $attributes['library'] ?? 'query';
        $this->token = $this->tokens->{$this->library};

    }

    public function import()
    {

        $sitemaps = $this->getSiteMaps();
        foreach ($sitemaps as $sitemap) {
            set_time_limit(0);
            echo $sitemap . "\r\n";
            $this->iterateSiteMap($sitemap);
        }
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

        foreach ($xml->url as $value) {

            $loc = $value->loc[0];
            if ($this->isValidAssessment($loc)) {
                $used_api_to_get_tags = $this->getLocInfo($loc);
                if ($used_api_to_get_tags) {
                    usleep(500000);
                    file_put_contents('query_imported_questions-' . date('Y-m-d') . '.txt', "$loc \r\n", FILE_APPEND);
                } else {
                    file_put_contents('query_skipped_imported_questions-' . date('Y-m-d') . '.txt', "No api used: git$loc \r\n", FILE_APPEND);
                }
            }
        }
    }

    public function updateTags()
    {
        //update based on either a single event or all possible tag update events
        $MindTouchEvent = MindTouchEvent::where('status', NULL)
            ->where('event', 'page.tag:update')
            ->get();

        foreach ($MindTouchEvent as $key => $mind_touch_event) {

            DB::beginTransaction();

            try {
                $page_id = $mind_touch_event->page_id;
                $question = Question::where('page_id', $page_id)->first();
                $parsed_url = parse_url($question->location);
                $page_info = $this->getPageInfoByParsedUrl($parsed_url);
                usleep(500000);
                $question->tags()->detach();
                $technology_and_tags = $this->getTechnologyAndTags($page_info);
                $this->addTagsToQuestion($question, $technology_and_tags['tags']);
                $mind_touch_event->status = 'updated';
                $mind_touch_event->save();
                DB::commit();
            } catch (Exception $e) {
                DB::rollback();
                Log::error("updateTags failed with page_id $page_id");
            }
        }
    }

    public function getLocInfo($loc)

    {
        try {
            $parsed_url = parse_url($loc);
            if (!isset($parsed_url['path'])) {
                //some were malformed with ?title=Assessment_Gallery instead of /Assessment_Gallery
                $loc = str_replace('?title=Assessment_Gallery', '/Assessment_Gallery', $loc);
                $parsed_url = parse_url($loc);
            }


            /*  $question_exists_in_db = DB::table('questions')->where('location', $loc)->first();
             if ($question_exists_in_db) {
                  return false;//didn't use the API
              }
            */
            $page_info = $this->getPageInfoByParsedUrl($parsed_url);

            $page_id = $page_info['@id'];
            $contents = $this->getContentsByPageId($page_id);
            $body = $contents['body'][0];
            if (strpos($body, '<iframe') !== false) {
                //file_put_contents('sitemap', "$final_url $page_id \r\n", FILE_APPEND);
                $technology_and_tags = $this->getTechnologyAndTags($page_info);

                $data = ['page_id' => $page_id,
                    'technology' => $technology_and_tags['technology'],
                    'location' => $loc,
                    'body' => $body];

                $question = Question::firstOrCreate($data);
                $this->addTagsToQuestion($question, $technology_and_tags['tags']);
            } else {
                file_put_contents('query_skipped_imported_questions-' . date('Y-m-d') . '.txt', "$loc \r\n", FILE_APPEND);
            }

        } catch (Exception $e) {
            file_put_contents('query_import_errors-' . date('Y-m-d') . '.txt', $e->getMessage() . ":  $loc \r\n", FILE_APPEND);
        }
        return true;//used the API

    }

    public function getContentsByPageId($page_id)
    {
        https://query.libretexts.org/@api/deki/pages/1860/contents

        $headers = ['Origin' => 'https://adapt.libretexts.org', 'x-deki-token' => $this->token];

        $final_url = "https://{$this->library}.libretexts.org/@api/deki/pages/{$page_id}/contents?dream.out.format=json";

        $response = $this->client->get($final_url, ['headers' => $headers]);
        return json_decode($response->getBody(), true);

    }


    function addTagsToQuestion($question, array $tags)
    {
        $Question = new Question;

        if ($tags) {
            foreach ($tags as $key => $tag) {
                $Question->addTag($tag, mb_strtolower($tag), $question);
            }
        }
    }

    public function getTechnologyFromBody($body)
    {

        if (strpos($body, 'h5p.libretexts.org') !== false) {
            return 'h5p';
        }
        if (strpos($body, 'webwork.libretexts.org') !== false) {
            return 'webwork';
        }
        if (strpos($body, 'imathas.libretexts.org') !== false) {
            return 'imathas';
        }
        return false;
    }

    public
    function getTechnologyAndTags($page_info)
    {
        $tags = [];
        $technology = false;
        if (isset($page_info['tags']['tag'])) {
            foreach ($page_info['tags']['tag'] as $key => $value) {
                $tag = $value['@value'] ?? false;
                if ($tag) {
                    if (strpos($tag, 'tech:') === 0) {
                        $technology = str_replace('tech:', '', $tag);
                    } else {
                        $tags[] = strtolower($tag);
                    }
                }
            }
        }
        return compact('tags', 'technology');
    }

    public function getPageInfoByPageId(int $page_id)
    {

        $headers = ['Origin' => 'https://adapt.libretexts.org', 'x-deki-token' => $this->token];

        $final_url = "https://{$this->library}.libretexts.org/@api/deki/pages/{$page_id}/info?dream.out.format=json";

        $response = $this->client->get($final_url, ['headers' => $headers]);
        return json_decode($response->getBody(), true);

    }

    public function getTagsByPageId(int $page_id)
    {
        $headers = ['Origin' => 'https://adapt.libretexts.org', 'x-deki-token' => $this->token];

        $final_url = "https://{$this->library}.libretexts.org/@api/deki/pages/{$page_id}/tags?dream.out.format=json";

        $response = $this->client->get($final_url, ['headers' => $headers]);
        return json_decode($response->getBody(), true);

    }

    public
    function getPageInfoByParsedUrl(array $parsed_url)
    {

        $path = substr($parsed_url['path'], 1);//get rid of trailing slash
        $headers = ['Origin' => 'https://adapt.libretexts.org', 'x-deki-token' => $this->token];

        $final_url = "https://{$this->library}.libretexts.org/@api/deki/pages/=" . urlencode($path) . '?dream.out.format=json';

        $response = $this->client->get($final_url, ['headers' => $headers]);
        $page_info = json_decode($response->getBody(), true);
        return $page_info;
    }

    public function updatePageInfoByPageId(int $page_id, $time_in_between = 2000000)
    {
        Log::info('updatePageInfoByPageId');
        $staging = (env('APP_ENV') === 'staging');
        if (!$page_id) {
            Log::info('No page id');
            return false;
        }
        if ($staging) {
            $page_id = 1939; //for testing purposes
            //Works if you update a tag or title is updated
        }
        //first save the latest updates
        try {


            //save the latest updates; this one should now be available.
            usleep($time_in_between); //not the best!  but allow for race conditions; want MindTouch to do the update first
            $page_info = $this->getPageInfoByPageId($page_id);
            Log::info($page_info);
            $question = Question::where('page_id', $page_id)->first();

            $technology = $this->getTechnologyAndTags($page_info);


            DB::beginTransaction();
            if (!$question) {
                Log::info('creating');
                //get the info from query then add to the database
                $question = Question::create(['page_id' => $page_id,
                    'technology' => $technology,
                    'location' => $page_info['uri.ui']]);
            } else {
                //the path may have changed so I need to update it
                $question->location = $page_info['uri.ui'];
                $question->save();
                Log::info('updating');

            }

            //now get the tags from Query and update
            $tag_info = $this->getTagsByPageId($page_id);
            $tags = [];
            Log::info('getting tags');
            Log::info($tag_info);
            if ($tag_info['@count'] > 0) {
                foreach ($tag_info['tag'] as $key => $tag) {
                    if (isset($tag['@value'])) {
                        $tags[] = $tag['@value'];
                    }
                }
                if ($tags) {
                    $this->addTagsToQuestion($question, $tags);
                }
            }
            DB::commit();
            return true;
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            return false;
        }
    }

    public
    function getSiteMaps()
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
