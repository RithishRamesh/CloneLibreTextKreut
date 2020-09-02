<?php

namespace App\Http\Controllers;

use App\Tag;
use App\Question;
use Illuminate\Http\Request;
use App\Question_Tag;

use App\Exceptions\Handler;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;

class QuestionController extends Controller
{
    public function getQuestionsByTags(Request $request, Question $question)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('viewAny', $question);

        if (!$authorized->allowed()) {

            $response['message'] = $authorized->message();
            return $response;
        }

        $page_id = $this->validatePageId($request);
        $questions = [];

            $question_ids = $page_id ? $this->getQuestionIdsByPageId($request, $response)
                : $this->getQuestionIdsByWordTags($request, $response);

            $questions = Question::whereIn('id', $question_ids)->get();


        foreach ($questions as $key => $question) {
            $questions[$key]['inAssignment'] = false;

        }

        return ['type' => 'success',
            'questions' => $questions];

    }
public function getQuestionIdsByPageId(Request $request, $response){
        $question_ids = [];
        return $question_ids;
}
    public function getQuestionIdsByWordTags(Request $request, $response)
    {
        $chosen_tags = DB::table('tags')
            ->whereIn('tag', $request->get('tags'))
            ->get()
            ->pluck('id');
        if (!$chosen_tags) return ['type' => 'error'];
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
        $pageIdTag = false;
        foreach ($request->get('tags') as $tag) {
            if (stripos($tag, 'pageid=') !== false) {
                $pageIdTag = true;
            }
        }

        if ($pageIdTag && (count($request->get('tags')) > 1)) {
            $response['message'] = "If you would like to search by page id, please don't include other tags.";
            echo json_encode($response);
            exit;
        }
        return $pageIdTag;
    }
}
