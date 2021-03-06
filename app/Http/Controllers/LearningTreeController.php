<?php

namespace App\Http\Controllers;


use App\Http\Requests\ImportLearningTreesRequest;
use App\Http\Requests\StoreLearningTreeInfo;
use App\Http\Requests\UpdateLearningTreeInfo;
use App\Http\Requests\UpdateNode;
use App\LearningTree;
use App\LearningTreeHistory;
use App\Libretext;
use App\Question;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

use App\Exceptions\Handler;
use \Exception;

class LearningTreeController extends Controller
{


    public function getLearningTreeByAssignmentQuestion()
    {
///after submitting, check if there's a learning tree
/// if there is


    }

    /**
     * @param ImportLearningTreesRequest $request
     * @param LearningTree $learningTree
     * @return array
     * @throws Exception
     */
    public function import(ImportLearningTreesRequest $request,
                           LearningTree $learningTree): array
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('import', $learningTree);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        try {

            $request->validated();


            $learning_tree_ids = explode(',', $request->learning_tree_ids);
            DB::beginTransaction();
            foreach ($learning_tree_ids as $learning_tree_id) {
                $learning_tree_to_import = LearningTree::find(trim($learning_tree_id))
                    ->replicate()
                    ->fill(['user_id' => $request->user()->id]);
                $learning_tree_to_import->save();

                $learningTreeHistory = new LearningTreeHistory();
                $learningTreeHistory->learning_tree = $learning_tree_to_import->learning_tree;
                $learningTreeHistory->learning_tree_id = $learning_tree_to_import->id;
                $learningTreeHistory->root_node_library = $learning_tree_to_import->root_node_library;
                $learningTreeHistory->root_node_page_id = $learning_tree_to_import->root_node_page_id;
                $learningTreeHistory->save();

            }
            $plural = str_contains($request->learning_tree_ids, ',') ? "s have been" : ' was';
            $response['type'] = 'success';
            $response['message'] = "The Learning Tree$plural imported.";

            DB::commit();
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error importing the learning trees.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    public function updateNode(UpdateNode $request, LearningTree $learningTree)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('updateNode', $learningTree);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        $response['type'] = 'error';
        $message = $this->learningTreeInAssignment($request, $learningTree, 'update the node');
        if ($message) {
            $response['message'] = $message;
            return $response;

        }
        try {
            $data = $request->validated();
            $validated_node = $this->validateLearningTreeNode($data['library'], $data['page_id']);
            if ($validated_node['type'] === 'error') {
                $response['message'] = $validated_node['message'];
                return $response;
            }
            if ($validated_node['body'] === '') {
                $response['message'] = "Are you sure that's a valid page id?  We're not finding any content on that page.";
                return $response;
            }

            $response['title'] = $validated_node['title'];
            $response['type'] = 'success';
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the node.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    /**
     * @param Request $request
     * @param LearningTree $learningTree
     * @return array
     * @throws Exception
     */
    public function createLearningTreeFromTemplate(Request $request, LearningTree $learningTree)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('createLearningTreeFromTemplate', $learningTree);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $response['type'] = 'error';

        try {
            DB::beginTransaction();
            $new_learning_tree = $learningTree->replicate();
            $new_learning_tree->title = $new_learning_tree->title . ' copy';
            $new_learning_tree->save();


            $learningTreeHistory = new LearningTreeHistory();
            $learningTreeHistory->root_node_library = $new_learning_tree->root_node_library;
            $learningTreeHistory->root_node_page_id = $new_learning_tree->root_node_page_id;
            $learningTreeHistory->learning_tree = $new_learning_tree->learning_tree;
            $learningTreeHistory->learning_tree_id = $new_learning_tree->id;
            $learningTreeHistory->save();
            DB::commit();
            $response['message'] = "The Learning Tree has been created.";
            $response['type'] = 'success';

        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error creating a learning tree from this template.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    public function learningTreeExists(Request $request)
    {
        $response['type'] = 'success';
        if (!LearningTree::where('id', $request->learning_tree_id)->exists()) {
            $response['type'] = 'error';
            $response['message'] = "We were not able to locate that Learning Tree.";
        }
        return $response;
    }

    public function index(Request $request, LearningTree $learningTree)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('index', $learningTree);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $response['type'] = 'error';

        try {
            $response['learning_trees'] = $learningTree->where('user_id', Auth::user()->id)->get();
            $response['type'] = 'success';

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving your learning trees.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    /**
     * @param Request $request
     * @param LearningTree $learningTree
     * @param LearningTreeHistory $learningTreeHistory
     * @return array
     * @throws Exception
     */
    public function updateLearningTree(Request $request,
                                       LearningTree $learningTree,
                                       LearningTreeHistory $learningTreeHistory)
    {

        $response['type'] = 'error';
        $authorized = Gate::inspect('update', $learningTree);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        $response['type'] = 'error';
        $learning_tree_old = json_decode($learningTree->learning_tree, true);
        $learning_tree_parsed = str_replace('\"', "'", $request->learning_tree);
        $learning_tree_new = json_decode($learning_tree_parsed, true);
        $no_change = $learning_tree_old === $learning_tree_new;

        if ($no_change) {
            $response['type'] = 'no_change';

        } else {
            try {
                $learningTree->learning_tree = $learning_tree_parsed;
                if ($request->root_node_library && $request->root_node_page_id) {
                    $learningTree->root_node_library = $request->root_node_library;
                    $learningTree->root_node_page_id = $request->root_node_page_id;
                }
                DB::beginTransaction();
                $learningTree->save();
                $this->saveLearningTreeToHistory($learningTree->root_node_library,
                    $learningTree->root_node_page_id,
                    $learningTree,
                    $learningTreeHistory);
                $response['type'] = 'success';
                $response['message'] = "The learning tree has been saved.";
                $response['no_change'] = $no_change;
                $response['can_undo'] = $learningTreeHistory->where('learning_tree_id', $learningTree->id)->get()->count() > 1;
                DB::commit();
            } catch (Exception $e) {
                DB::rollback();
                $h = new Handler(app());
                $h->report($e);
                $response['message'] = "There was an error saving the learning tree.  Please try again or contact us for assistance.";
            }
        }
        return $response;

    }

    public function saveLearningTreeToHistory(string $library, int $page_id, LearningTree $learningTree, LearningTreeHistory $learningTreeHistory)
    {
        $learningTreeHistory->root_node_library = $library;
        $learningTreeHistory->root_node_page_id = $page_id;
        $learningTreeHistory->learning_tree_id = $learningTree->id;
        $learningTreeHistory->learning_tree = $learningTree->learning_tree;
        $learningTreeHistory->save();
    }

    /**
     * @param UpdateLearningTreeInfo $request
     * @param LearningTree $learningTree
     * @return array
     * @throws Exception
     */
    public function updateLearningTreeInfo(UpdateLearningTreeInfo $request, LearningTree $learningTree)
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('update', $learningTree);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        $response['type'] = 'error';


        try {

            $data = $request->validated();
            $learningTree->title = $data['title'];
            $learningTree->description = $data['description'];
            $learningTree->save();

            $response['type'] = 'success';
            $response['message'] = "The Learning Tree has been updated.";
        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error updating the learning tree.  Please try again or contact us for assistance.";
        }
        return $response;

    }

    /**
     * @param StoreLearningTreeInfo $request
     * @param LearningTree $learningTree
     * @param LearningTreeHistory $learningTreeHistory
     * @return array
     * @throws Exception
     */
    public function storeLearningTreeInfo(StoreLearningTreeInfo $request,
                                          LearningTree $learningTree,
                                          LearningTreeHistory $learningTreeHistory): array
    {
        $response['type'] = 'error';
        $authorized = Gate::inspect('store', $learningTree);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }

        $response['type'] = 'error';

        try {

            $data = $request->validated();
            $validated_node = $this->validateLearningTreeNode($data['library'], $data['page_id']);
            if ($validated_node['type'] === 'error') {
                $response['message'] = $validated_node['message'];
                return $response;
            }
            if ($validated_node['body'] === '') {
                $response['message'] = "Are you sure that's a valid page id?  We're not finding any content on that page.";
                return $response;
            }
            $learningTree->root_node_page_id = $data['page_id'];
            $learningTree->root_node_library = $data['library'];
            $learningTree->title = $data['title'];
            $learningTree->description = $data['description'];


            $learningTree->user_id = Auth::user()->id;
            $shortened_title = $this->shortenTitle($validated_node['title']);
            DB::beginTransaction();
            $learningTree->learning_tree = $this->getRootNode($shortened_title, $data['library'], $request->text, $request->color, $data['page_id']);
            $learningTree->save();
            $this->saveLearningTreeToHistory($learningTree->root_node_library, $learningTree->root_node_page_id, $learningTree, $learningTreeHistory);


            $response['type'] = 'success';
            $response['learning_tree'] = $learningTree->learning_tree;
            $response['message'] = "The Learning Tree has been created.";
            $response['learning_tree_id'] = $learningTree->id;
            DB::commit();
        } catch (Exception $e) {
            DB::rollback();
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error saving the learning tree.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    /**
     * @param $title
     * @return string
     */
    public function shortenTitle($title): string
    {
        $title = trim($title);
        return strlen($title) < 28 ? $title : substr($title, 0, 23) . '...';
    }

    public function getRootNode(string $title, string $library_value, string $library_text, string $library_color, int $page_id)
    {
        $html = "<div class='blockelem noselect block' style='left: 363px; top: 215px; border: 2px solid; color: $library_color;'><input type='hidden' name='blockelemtype' class='blockelemtype' value='1'><input type='hidden' name='blockid' class='blockid' value='0'><div class='blockyleft'><input type='hidden' name='page_id' value='$page_id'><input type='hidden' name='library' value='$library_value'><p class='blockyname'><img src='/assets/img/{$library_value}.svg'><span class='library'>{$library_text}</span> - <span class='page_id'>$page_id</span></p></div><div class='blockydiv'></div><div class='blockyinfo'>$title</div></div><div class='indicator invisible' style='left: 154px; top: 119px;'></div>";
        return <<<EOT
 {"html":"$html","blockarr":[{"childwidth":318,"parent":-1,"id":0,"x":825,"y":274,"width":318,"height":109}],"blocks":[{"id":0,"parent":-1,"data":[{"name":"blockelemtype","value":"1"},{"name":"blockid","value":"0"}],"attr":[{"class":"blockelem noselect block"},{"style":"left: 363px; top: 215px; border: 2px solid; color: {$library_color};"}]}]}
EOT;


    }

    public function getLearningTreeByUserAndQuestionId($user_id, $question_id)
    {
        return DB::table('learning_trees')
            ->where('question_id', $question_id)
            ->where('user_id', $user_id)
            ->pluck('learning_tree');
    }

    public function getDefaultLearningTreeByQuestionId(int $question_id)
    {
        return DB::table('learning_trees')
            ->where('question_id', $question_id)
            ->orderBy('created_at', 'asc')
            ->pluck('learning_tree');
    }

    public function getNodeLibraryTextFromLearningTree($learning_tree)
    {
        $re = '/(?<=<span class=\'library\'>).*?(?=<\/span>)/m';
        preg_match($re, $learning_tree, $matches);

        return $matches[0] ?? 'Could not find library';
    }

    public function getNodePageIdFromLearningTree($learning_tree)
    {
        $re = '/(?<=<span class=\'page_id\'>).*?(?=<\/span>)/m';
        preg_match($re, $learning_tree, $matches);

        return $matches[0] ?? 'Could not find Page Id';
    }

    public function show(Request $request,
                         LearningTree $learningTree,
                         LearningTreeHistory $learningTreeHistory)
    {
        //anybody who is logged in can do this!
        $response['type'] = 'error';
        try {

            $response['type'] = 'success';
            $response['learning_tree'] = $learningTree->learning_tree;
            $response['title'] = $learningTree->title;
            $response['description'] = $learningTree->description;
            $response['library'] = $this->getNodeLibraryTextFromLearningTree($learningTree->learning_tree);
            $response['page_id'] = $this->getNodePageIdFromLearningTree($learningTree->learning_tree);
            $response['can_undo'] = $learningTreeHistory->where('learning_tree_id', $learningTree->id)->get()->count() > 1;

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving the learning tree.  Please try again or contact us for assistance.";
        }
        return $response;
    }

    public function getDefaultLearningTree()
    {
        return <<<EOT
{"html":"<div class="blockelem noselect block" style="left: 363px; top: 215px; border: 2px solid; color: rgb(18, 123, 196);"><input type="hidden" name="blockelemtype" class="blockelemtype" value="1"><input type="hidden" name="blockid" class="blockid" value="0"><div class="blockyleft"><p class="blockyname"><img src="/assets/img/adapt.svg">Assessment</p></div><div class="blockydiv"></div><div class="blockyinfo">The original question.</div></div><div class="indicator invisible" style="left: 154px; top: 119px;"></div>","blockarr":[{"childwidth":318,"parent":-1,"id":0,"x":825,"y":274,"width":318,"height":109}],"blocks":[{"id":0,"parent":-1,"data":[{"name":"blockelemtype","value":"1"},{"name":"blockid","value":"0"}],"attr":[{"class":"blockelem noselect block"},{"style":"left: 363px; top: 215px; border: 2px solid; color: rgb(18, 123, 196);"}]}]}
EOT;

    }

    /**
     * Display the specified resource.
     *
     * @param Request $request
     * @param Question $question
     * @return array
     * @throws Exception
     */
    public function showByQuestion(Request $request, Question $question)
    {
        //anybody who is logged in can do this!
        $response['type'] = 'error';
        try {
            $learning_tree = $this->getLearningTreeByUserAndQuestionId(Auth::user()->id, $question->id);

            if ($learning_tree->isEmpty()) {
                $learning_tree = $this->getDefaultLearningTreeByQuestionId($question->id);
            }

            if ($learning_tree->isEmpty()) {
                $learning_tree = $this->getDefaultLearningTree();
            }

            $response['type'] = 'success';
            $response['learning_tree'] = $learning_tree;

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error retrieving the learning tree.  Please try again or contact us for assistance.";
        }
        return $response;


    }


    /**
     * @param Request $request
     * @param LearningTree $learningTree
     * @param LearningTreeHistory $learningTreeHistory
     * @return array
     * @throws Exception
     */
    public function destroy(Request $request,
                            LearningTree $learningTree,
                            LearningTreeHistory $learningTreeHistory): array
    {
        //anybody who is logged in can do this!
        $response['type'] = 'error';
        $authorized = Gate::inspect('destroy', $learningTree);

        if (!$authorized->allowed()) {
            $response['message'] = $authorized->message();
            return $response;
        }
        try {
            $message = $this->learningTreeInAssignment($request, $learningTree, 'delete it');
            if ($message) {
                $response['message'] = $message;
                return $response;
            }
            $learningTree->learningTreeHistories()->delete();
            $learningTree->delete();
            $response['type'] = 'info';
            $response['message'] = "The Learning Tree has been deleted.";

        } catch (Exception $e) {
            $h = new Handler(app());
            $h->report($e);
            $response['message'] = "There was an error deleting the learning Tree.  Please try again or contact us for assistance.";
        }
        return $response;


    }

    /**
     * @param Request $request
     * @param LearningTree $learningTree
     * @param string $action
     * @return string
     */
    public function learningTreeInAssignment(Request $request, learningTree $learningTree, string $action): string
    {

        $assignment_learning_tree_info = DB::table('assignment_question_learning_tree')->where('learning_tree_id', $learningTree->id)
            ->first();
        if (!$assignment_learning_tree_info) {
            return '';
        }

        $assignment_info = DB::table('assignment_question_learning_tree')
            ->join('assignment_question', 'assignment_question_id', '=', 'assignment_question.id')
            ->join('assignments', 'assignment_id', '=', 'assignments.id')
            ->join('courses', 'course_id', '=', 'courses.id')
            ->join('users', 'user_id', '=', 'users.id')
            ->where('assignment_question_id', $assignment_learning_tree_info->assignment_question_id)
            ->select('users.id',
                DB::raw('assignments.name AS assignment'),
                DB::raw('courses.name AS course')
            )
            ->first();


        return
            ($assignment_info->id === $request->user()->id)
                ? "It looks like you're using this Learning Tree in $assignment_info->course --- $assignment_info->assignment.  Please first remove that question from the assignment before attempting to $action."
                : "It looks like another instructor is using this Learning Tree so you won't be able to $action.";

    }

    public function validateLearningTreeNode(string $library, int $pageId)
    {

        $response['type'] = 'error';
        try {
            $Libretext = new Libretext(['library' => $library]);
            $contents = $Libretext->getContentsByPageId($pageId);
            $response['body'] = $contents['body'];
            $response['title'] = $contents['@title'] ?? 'Title';
            $response['title'] = str_replace('"', '&quot;', $response['title']);
            $response['type'] = 'success';
        } catch (Exception $e) {
            if (strpos($e->getMessage(), '403 Forbidden') === false) {
                //some other error besides forbidden
                $h = new Handler(app());
                $h->report($e);
                $response['message'] = "We were not able to validate this Learning Tree node.  Please double check your library and page id or contact us for assistance.";
                return $response;
            } else {
                try {
                    $contents = $Libretext->getBodyFromPrivatePage($pageId);
                    $title = '@title';
                    $response['title'] = $contents->{$title} ?? 'Title';
                    $response['title'] = str_replace('"', '&quot;', $response['title']);
                    $response['body'] = $contents->body[0];
                    $response['type'] = 'success';
                } catch (Exception $e) {
                    $h = new Handler(app());
                    $h->report($e);
                    $response['message'] = "We were not able to validate this Learning Tree node.  Please double check your library and page id or contact us for assistance.";
                }
            }
        }
        $response['title'] = $this->shortenTitle($response['title']);
        return $response;
    }
}
