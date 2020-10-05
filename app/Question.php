<?php

namespace App;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Question extends Model
{

    protected $fillable = ['title', 'author', 'page_id', 'technology_iframe', 'non_technology', 'location','technology'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        ini_set('memory_limit', '2G');

    }

    public function getUrlLinkText($url)
    {
        $matches = [];
        preg_match('/\>(.*)<\/a>/', $url, $matches);
        /*** return the match ***/
        return $matches[1];
    }

    public function getCreatedAt($time)
    {
        $matches = [];
        preg_match('/\>(.*)<\/time>/', $time, $matches);
        /*** return the match ***/
        return $matches[1];
    }

    public function tags()
    {
        return $this->belongsToMany('App\Tag')->withTimestamps();
    }


    public function getH5PQuestions(int $offset)
    {
        /** [
         * "<a href=\"https://h5p.libretexts.org/wp-admin/admin.php?page=h5p&task=show&id=1464\">Cap.5: Videos y actividad.</a>",
         * { "id": "H5P.Column", "title": "Column" },
         * { "id": "14", "title": "Anaid Stere-Lugo" },
         * [ { "id": "686", "title": "la ropa" }, { "id": "689", "title": "de compras" }, { "id": "691", "title": "adjetivos." }, { "id": "697", "title": "Video" } ],
         * "<time datetime=\"2020-07-11T02:44:46+00:00\" title=\"2 days ago\">2020/07/11</time>",
         * "1464",
         * "<a href=\"https://h5p.libretexts.org/wp-admin/admin.php?page=h5p&task=results&id=1464\">Results</a>",
         * "<a href=\"https://h5p.libretexts.org/wp-admin/admin.php?page=h5p_new&id=1464\">Edit</a>" ]
         */

        $login_user = getenv('H5P_USERNAME');
        $login_pass = getenv('H5P_PASSWORD');
        $login_url = 'https://h5p.libretexts.org/wp-login.php';
        $visit_url = "https://h5p.libretexts.org/wp-admin/admin-ajax.php?action=h5p_contents&limit=100&offset=$offset&sortBy=4&sortDir=0";
        $cookie_file = '/cookie.txt';
        $http_agent = "Mozilla/5.0 (Windows; U; Windows NT 5.1; en-US; rv:1.8.1.6) Gecko/20070725 Firefox/2.0.0.6";

        $fields_string = http_build_query(['log' => $login_user,
            'pwd' => $login_pass,
            'wp-submit' => 'Log%20In',
            'redirect_to' => $visit_url]);

        $ch = curl_init();

        curl_setopt($ch, CURLOPT_URL, $login_url);
        curl_setopt($ch, CURLOPT_COOKIEJAR, $cookie_file);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_USERAGENT, $http_agent);
        curl_setopt($ch, CURLOPT_TIMEOUT, 60);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
        curl_setopt($ch, CURLOPT_REFERER, $login_url);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $fields_string);
        curl_setopt($ch, CURLOPT_POST, 1);
        $questions = curl_exec($ch);

        if (curl_errno($ch)) {
            $error_msg = curl_error($ch);
        }
        curl_close($ch);

        if (isset($error_msg)) {
            echo $error_msg;
            exit;
        }
        return json_decode($questions);
    }

    public function addTag($key, $tag, $question)
    {
        if ($key) {
            $tag = Tag::firstOrCreate(compact('tag'));
            if (!$question->tags->contains($tag->id)) {
                $question->tags()->attach($tag->id);
            }
        }
    }

    public function storeWebwork()
    {
        try {
            $webwork = DB::connection('webwork');
            echo "Connected to webwork\r\n";
            $questions = $webwork->table('OPL_path')
                ->join('OPL_pgfile', 'OPL_path.path_id', '=', 'OPL_pgfile.path_id')
                ->leftJoin('OPL_pgfile_keyword', 'OPL_pgfile_keyword.pgfile_id', '=', 'OPL_pgfile.pgfile_id')
                ->leftJoin('OPL_keyword', 'OPL_pgfile_keyword.keyword_id', '=', 'OPL_keyword.keyword_id')
                ->leftJoin('OPL_author', 'OPL_author.author_id', '=', 'OPL_pgfile.author_id')
                ->leftJoin('OPL_section', 'OPL_pgfile.DBsection_id', '=', 'OPL_section.section_id')
                ->leftJoin('OPL_chapter', 'OPL_section.chapter_id', '=', 'OPL_chapter.chapter_id')
                ->leftJoin('OPL_textbook', 'OPL_chapter.chapter_id', '=', 'OPL_textbook.textbook_id')
                ->select('keyword',
                    'level',
                    'path',
                    DB::raw("CONCAT(`firstname`,' ',`lastname`) AS author"),
                    DB::raw("CONCAT(`path`,'/',`filename`) AS page_id"),
                    DB::raw("CONCAT(`title`,' - ',OPL_chapter.name,' - ',OPL_section.name,': ',`firstname`,' ',`lastname`) AS textbook_source"))
                ->get();
            DB::disconnect('webwork');
            echo count($questions) . " questions\r\n";
            echo "Disconnected from webwork\r\n";
            echo "Selected questions\r\n";
            foreach ($questions as $value) {
                $data = ['author' => $value->author,
                    'page_id' => $value->page_id,
                    'technology' => 'webwork'];
                $question = Question::firstOrCreate($data);
                $this->addTag($value->keyword, mb_strtolower($value->keyword), $question);
                $this->addTag($value->level, "Difficulty Level = {$value->level}", $question);
                $this->addTag($value->textbook_source, $value->textbook_source, $question);
                $this->addTag($value->path, $value->path, $question);
                $question->refresh();
                echo $value->page_id . "\r\n";
            }
            echo "Inserted questions\r\n";
        } catch (Exception $e) {
            echo $e->getMessage();
        }

    }

    public function storeH5P()
    {
        $offset = 0;
        $questions = $this->getH5PQuestions($offset);
        while ($questions->rows) {
            echo $offset;
            foreach ($questions->rows as $question) {
                $title = $this->getUrlLinkText($question[0]);
                $author = $question[2]->title;
                $tag_info = $question[3];
                //$created_at = $this->getCreatedAt($question[4]);  Do I need this?
                $page_id = $question[5];
                $data = compact('title', 'author', 'page_id') + ['technology' => 'h5p'];
                $question = $this->firstOrCreate($data);
                if ($tag_info) {
                    foreach ($tag_info as $value) {
                        $tag_id = Tag::firstOrCreate(['tag' => mb_strtolower($value->title)]);
                        if (!$question->tags->contains($tag_id)) {
                            $question->tags()->attach($tag_id);
                        }
                    }

                    //store question info in the question table
                    //title, author, id, created at, question_and_tag_pivot_id
                    //store the tags in the tag table if they don't already exist

                }
            }
            $offset += 100;
            $questions = $this->getH5PQuestions($offset);
        }
        echo "\r\n";

    }

    public function store()
    {
        $this->storeWebwork();
        $this->storeH5P();
    }

}
