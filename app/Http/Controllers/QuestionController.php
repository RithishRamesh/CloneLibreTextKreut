<?php

namespace App\Http\Controllers;

use App\Tag;
use App\Question;
use Illuminate\Http\Request;
use App\Question_Tag;
use App\Traits\IframeFormatter;

use App\Exceptions\Handler;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class QuestionController extends Controller
{
    use IframeFormatter;
    public function getQuestionsByTags(Request $request, Question $Question)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('viewAny', $Question);

        if (!$authorized->allowed()) {

            $response['message'] = $authorized->message();
            return $response;
        }

        $page_id = $this->validatePageId($request);


        $question_ids = $page_id ? $this->getQuestionIdsByPageId($page_id, $Question)
            : $this->getQuestionIdsByWordTags($request);

        $questions = Question::select('id','page_id','body')->whereIn('id', $question_ids)->get();

        foreach ($questions as $key => $question) {
            $questions[$key]['inAssignment'] = false;
            $questions[$key]['iframe_id'] =  $this->createIframeId();
            $questions[$key]['body'] = $this->formatIframe($questions[$key]['body'], $questions[$key]['iframe_id']);

        }

        return ['type' => 'success',
            'questions' => $questions];

    }

    public function getQuestionIdsByPageId(int $page_id, Question $Question)
    {
        $question = $Question::where('page_id', $page_id)->first();
        if (!$question) {
            echo json_encode(['type' => 'error', 'message' => 'That is not a valid query Page Id.']);
            exit;
        }
        return [$question->id];
    }

    public function getQuestionIdsByWordTags(Request $request)
    {
        $chosen_tags = DB::table('tags')
            ->whereIn('tag', $request->get('tags'))
            ->get()
            ->pluck('id');
        if (!$chosen_tags) {
            echo json_encode([
                'type' => 'error',
                'message' => 'We could not find the tags in our database.']);
            exit;

        }
        $question_ids_grouped_by_tag = [];
        //get all of the question ids for each of the tags
        foreach ($chosen_tags as $key => $chosen_tag) {
            $question_ids_grouped_by_tag[$key] = DB::table('question_tag')
                ->select('question_id')
                ->where('tag_id', '=', $chosen_tag)
                ->get()
                ->pluck('question_id')->toArray();
            if (!$question_ids_grouped_by_tag[$key]) {
                echo json_encode(['type' => 'error',
                    'message' => 'There are no questions associated with those tags.']);
                exit;
            }
        }
        //now intersect them for each group
        $question_ids = $question_ids_grouped_by_tag[0];
        $intersected_question_ids = [];
        foreach ($question_ids_grouped_by_tag as $question_group) {
            $intersected_question_ids = array_intersect($question_ids, $question_group);
        }
        if (!count($intersected_question_ids)) {
            echo json_encode(['type' => 'error',
                'message' => 'There are no questions associated with those tags.']);
            exit;
        }
        return $intersected_question_ids;
    }

    public function validatePageId(Request $request)
    {
        $page_id = false;

        foreach ($request->get('tags') as $tag) {
            if (stripos($tag, 'pageid=') !== false) {
                $page_id = str_ireplace('pageid=', '', $tag);
            }
        }

        if ($page_id && (count($request->get('tags')) > 1)) {
            $response['message'] = "If you would like to search by page id, please don't include other tags.";
            echo json_encode($response);
            exit;
        }
        return $page_id;
    }
}
